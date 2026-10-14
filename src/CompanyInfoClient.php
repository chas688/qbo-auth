<?php

namespace CharlesHarmon\QboAuth;

use RuntimeException;

class CompanyInfoClient
{
    public function __construct(private OAuthClient $client) {}

    /**
     * Fetch the company's CompanyInfo entity from QuickBooks Online.
     *
     * @return array{name: ?string, legal_name: ?string, email: ?string, phone: ?string, website: ?string, address: ?string, country: ?string, start_date: ?string, fiscal_year_start_month: ?string, created_at: ?string}
     *
     * @throws RuntimeException when QuickBooks returns an error.
     */
    public function fetch(string $accessToken, string $realmId): array
    {
        $dataService = $this->client->dataService($accessToken, $realmId);
        $companyInfo = $dataService->getCompanyInfo();
        $error = $dataService->getLastError();

        if ($error || $companyInfo === null) {
            $status = $error ? $error->getHttpStatusCode() : null;

            throw new RuntimeException('QuickBooks returned an error'.($status ? " (HTTP {$status})" : '').' while loading company info.');
        }

        $address = $companyInfo->CompanyAddr;

        return [
            'name' => $companyInfo->CompanyName,
            'legal_name' => $companyInfo->LegalName,
            'email' => $this->nestedValue($companyInfo->Email, 'Address') ?? $this->nestedValue($companyInfo->CompanyEmailAddr, 'Address'),
            'phone' => $this->nestedValue($companyInfo->PrimaryPhone, 'FreeFormNumber'),
            'website' => $this->nestedValue($companyInfo->WebAddr, 'URI') ?? $this->nestedValue($companyInfo->CompanyURL, 'URI'),
            'address' => $address ? $this->formatAddress([$address->Line1, $address->City, $address->CountrySubDivisionCode, $address->PostalCode]) : null,
            'country' => $companyInfo->Country,
            'start_date' => $companyInfo->CompanyStartDate,
            'fiscal_year_start_month' => $companyInfo->FiscalYearStartMonth,
            'created_at' => $companyInfo->MetaData?->CreateTime,
        ];
    }

    /**
     * Read a value from an SDK field that holds either an entity object or, when QuickBooks returns it empty, a plain string.
     */
    private function nestedValue(mixed $field, string $property): ?string
    {
        $value = is_object($field) ? ($field->{$property} ?? null) : $field;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<int, ?string> $parts */
    private function formatAddress(array $parts): ?string
    {
        $address = implode(', ', array_filter($parts, fn (?string $part): bool => $part !== null && $part !== ''));

        return $address === '' ? null : $address;
    }
}
