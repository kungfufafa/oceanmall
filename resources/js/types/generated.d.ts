declare namespace App {
    namespace DTO {
        export type AddressData = {
            firstName: string | null;
            lastName: string | null;
            company: string | null;
            streetAddress: string;
            streetAddressPlus: string | null;
            postalCode: string;
            city: string;
            phoneNumber: string | null;
            countryId: number;
        };
        export type CountryByZoneData = {
            zoneId: number;
            zoneCode: string | null;
            zoneName: string;
            countryId: number;
            countryName: string;
            countryCode: string;
            countryFlag: string;
            currencyCode: string;
        };
        export type PriceData = {
            amount: undefined;
            compare: undefined | null;
            percentage: number | null;
        };
        export type ProductReviewsData = {
            reviews: undefined;
            averageRating: number;
            totalCount: number;
        };
    }
}
