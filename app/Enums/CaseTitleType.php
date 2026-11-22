<?php

namespace App\Enums;

enum CaseTitleType: string
{
    case AggravatedAssault = 'aggravated_assault';
    case MiscellaneousTheftLarceny = 'miscellaneous_theft_larceny';
    case ViolationsOfRulesAndRegulations = 'violations_of_rules_and_regulations';
    case SocialCrimes = 'social_crimes';
    case SaleOrPossessionOfProhibitedWeapon = 'sale_or_possession_of_prohibited_weapon';
    case Homicide = 'homicide';
    case InsuranceCrimes = 'insurance_crimes';
    case TradingWithoutBusinessLicense = 'trading_without_business_license';
    case DamageToAnotherPersonsProperty = 'damage_to_another_persons_property';
    case DeceptionAndFraud = 'deception_and_fraud';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AggravatedAssault => 'Aggravated Assault',
            self::MiscellaneousTheftLarceny => 'Miscellaneous Theft / Larceny',
            self::ViolationsOfRulesAndRegulations => 'Violations of Rules and Regulations',
            self::SocialCrimes => 'Social Crimes',
            self::SaleOrPossessionOfProhibitedWeapon => 'Sale or Possession of Prohibited Weapon',
            self::Homicide => 'Homicide',
            self::InsuranceCrimes => 'Insurance Crimes',
            self::TradingWithoutBusinessLicense => 'Trading Without a Business License',
            self::DamageToAnotherPersonsProperty => "Damage to Another Person's Property",
            self::DeceptionAndFraud => 'Deception and Fraud',
            self::Other => 'Other / Custom',
        };
    }
}
