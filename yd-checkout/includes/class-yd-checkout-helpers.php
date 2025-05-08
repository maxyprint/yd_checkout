<?php
/**
 * Helper functions for YDesign Checkout
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes
 */

class Yd_Checkout_Helpers {

    /**
     * Get list of countries with ISO codes
     *
     * @return array Associative array of country codes => country names
     */
    public static function get_countries() {
        return array(
            'AF' => __('Afghanistan', 'yd-checkout'),
            'AX' => __('Åland Islands', 'yd-checkout'),
            'AL' => __('Albania', 'yd-checkout'),
            'DZ' => __('Algeria', 'yd-checkout'),
            'AS' => __('American Samoa', 'yd-checkout'),
            'AD' => __('Andorra', 'yd-checkout'),
            'AO' => __('Angola', 'yd-checkout'),
            'AI' => __('Anguilla', 'yd-checkout'),
            'AQ' => __('Antarctica', 'yd-checkout'),
            'AG' => __('Antigua and Barbuda', 'yd-checkout'),
            'AR' => __('Argentina', 'yd-checkout'),
            'AM' => __('Armenia', 'yd-checkout'),
            'AW' => __('Aruba', 'yd-checkout'),
            'AU' => __('Australia', 'yd-checkout'),
            'AT' => __('Austria', 'yd-checkout'),
            'AZ' => __('Azerbaijan', 'yd-checkout'),
            'BS' => __('Bahamas', 'yd-checkout'),
            'BH' => __('Bahrain', 'yd-checkout'),
            'BD' => __('Bangladesh', 'yd-checkout'),
            'BB' => __('Barbados', 'yd-checkout'),
            'BY' => __('Belarus', 'yd-checkout'),
            'BE' => __('Belgium', 'yd-checkout'),
            'BZ' => __('Belize', 'yd-checkout'),
            'BJ' => __('Benin', 'yd-checkout'),
            'BM' => __('Bermuda', 'yd-checkout'),
            'BT' => __('Bhutan', 'yd-checkout'),
            'BO' => __('Bolivia', 'yd-checkout'),
            'BQ' => __('Bonaire, Sint Eustatius and Saba', 'yd-checkout'),
            'BA' => __('Bosnia and Herzegovina', 'yd-checkout'),
            'BW' => __('Botswana', 'yd-checkout'),
            'BV' => __('Bouvet Island', 'yd-checkout'),
            'BR' => __('Brazil', 'yd-checkout'),
            'IO' => __('British Indian Ocean Territory', 'yd-checkout'),
            'BN' => __('Brunei Darussalam', 'yd-checkout'),
            'BG' => __('Bulgaria', 'yd-checkout'),
            'BF' => __('Burkina Faso', 'yd-checkout'),
            'BI' => __('Burundi', 'yd-checkout'),
            'KH' => __('Cambodia', 'yd-checkout'),
            'CM' => __('Cameroon', 'yd-checkout'),
            'CA' => __('Canada', 'yd-checkout'),
            'CV' => __('Cape Verde', 'yd-checkout'),
            'KY' => __('Cayman Islands', 'yd-checkout'),
            'CF' => __('Central African Republic', 'yd-checkout'),
            'TD' => __('Chad', 'yd-checkout'),
            'CL' => __('Chile', 'yd-checkout'),
            'CN' => __('China', 'yd-checkout'),
            'CX' => __('Christmas Island', 'yd-checkout'),
            'CC' => __('Cocos (Keeling) Islands', 'yd-checkout'),
            'CO' => __('Colombia', 'yd-checkout'),
            'KM' => __('Comoros', 'yd-checkout'),
            'CG' => __('Congo', 'yd-checkout'),
            'CD' => __('Congo, Democratic Republic of the', 'yd-checkout'),
            'CK' => __('Cook Islands', 'yd-checkout'),
            'CR' => __('Costa Rica', 'yd-checkout'),
            'CI' => __('Côte d\'Ivoire', 'yd-checkout'),
            'HR' => __('Croatia', 'yd-checkout'),
            'CU' => __('Cuba', 'yd-checkout'),
            'CW' => __('Curaçao', 'yd-checkout'),
            'CY' => __('Cyprus', 'yd-checkout'),
            'CZ' => __('Czech Republic', 'yd-checkout'),
            'DK' => __('Denmark', 'yd-checkout'),
            'DJ' => __('Djibouti', 'yd-checkout'),
            'DM' => __('Dominica', 'yd-checkout'),
            'DO' => __('Dominican Republic', 'yd-checkout'),
            'EC' => __('Ecuador', 'yd-checkout'),
            'EG' => __('Egypt', 'yd-checkout'),
            'SV' => __('El Salvador', 'yd-checkout'),
            'GQ' => __('Equatorial Guinea', 'yd-checkout'),
            'ER' => __('Eritrea', 'yd-checkout'),
            'EE' => __('Estonia', 'yd-checkout'),
            'ET' => __('Ethiopia', 'yd-checkout'),
            'FK' => __('Falkland Islands (Malvinas)', 'yd-checkout'),
            'FO' => __('Faroe Islands', 'yd-checkout'),
            'FJ' => __('Fiji', 'yd-checkout'),
            'FI' => __('Finland', 'yd-checkout'),
            'FR' => __('France', 'yd-checkout'),
            'GF' => __('French Guiana', 'yd-checkout'),
            'PF' => __('French Polynesia', 'yd-checkout'),
            'TF' => __('French Southern Territories', 'yd-checkout'),
            'GA' => __('Gabon', 'yd-checkout'),
            'GM' => __('Gambia', 'yd-checkout'),
            'GE' => __('Georgia', 'yd-checkout'),
            'DE' => __('Germany', 'yd-checkout'),
            'GH' => __('Ghana', 'yd-checkout'),
            'GI' => __('Gibraltar', 'yd-checkout'),
            'GR' => __('Greece', 'yd-checkout'),
            'GL' => __('Greenland', 'yd-checkout'),
            'GD' => __('Grenada', 'yd-checkout'),
            'GP' => __('Guadeloupe', 'yd-checkout'),
            'GU' => __('Guam', 'yd-checkout'),
            'GT' => __('Guatemala', 'yd-checkout'),
            'GG' => __('Guernsey', 'yd-checkout'),
            'GN' => __('Guinea', 'yd-checkout'),
            'GW' => __('Guinea-Bissau', 'yd-checkout'),
            'GY' => __('Guyana', 'yd-checkout'),
            'HT' => __('Haiti', 'yd-checkout'),
            'HM' => __('Heard Island and McDonald Islands', 'yd-checkout'),
            'VA' => __('Holy See (Vatican City State)', 'yd-checkout'),
            'HN' => __('Honduras', 'yd-checkout'),
            'HK' => __('Hong Kong', 'yd-checkout'),
            'HU' => __('Hungary', 'yd-checkout'),
            'IS' => __('Iceland', 'yd-checkout'),
            'IN' => __('India', 'yd-checkout'),
            'ID' => __('Indonesia', 'yd-checkout'),
            'IR' => __('Iran, Islamic Republic of', 'yd-checkout'),
            'IQ' => __('Iraq', 'yd-checkout'),
            'IE' => __('Ireland', 'yd-checkout'),
            'IM' => __('Isle of Man', 'yd-checkout'),
            'IL' => __('Israel', 'yd-checkout'),
            'IT' => __('Italy', 'yd-checkout'),
            'JM' => __('Jamaica', 'yd-checkout'),
            'JP' => __('Japan', 'yd-checkout'),
            'JE' => __('Jersey', 'yd-checkout'),
            'JO' => __('Jordan', 'yd-checkout'),
            'KZ' => __('Kazakhstan', 'yd-checkout'),
            'KE' => __('Kenya', 'yd-checkout'),
            'KI' => __('Kiribati', 'yd-checkout'),
            'KP' => __('Korea, Democratic People\'s Republic of', 'yd-checkout'),
            'KR' => __('Korea, Republic of', 'yd-checkout'),
            'KW' => __('Kuwait', 'yd-checkout'),
            'KG' => __('Kyrgyzstan', 'yd-checkout'),
            'LA' => __('Lao People\'s Democratic Republic', 'yd-checkout'),
            'LV' => __('Latvia', 'yd-checkout'),
            'LB' => __('Lebanon', 'yd-checkout'),
            'LS' => __('Lesotho', 'yd-checkout'),
            'LR' => __('Liberia', 'yd-checkout'),
            'LY' => __('Libya', 'yd-checkout'),
            'LI' => __('Liechtenstein', 'yd-checkout'),
            'LT' => __('Lithuania', 'yd-checkout'),
            'LU' => __('Luxembourg', 'yd-checkout'),
            'MO' => __('Macao', 'yd-checkout'),
            'MK' => __('Macedonia, the Former Yugoslav Republic of', 'yd-checkout'),
            'MG' => __('Madagascar', 'yd-checkout'),
            'MW' => __('Malawi', 'yd-checkout'),
            'MY' => __('Malaysia', 'yd-checkout'),
            'MV' => __('Maldives', 'yd-checkout'),
            'ML' => __('Mali', 'yd-checkout'),
            'MT' => __('Malta', 'yd-checkout'),
            'MH' => __('Marshall Islands', 'yd-checkout'),
            'MQ' => __('Martinique', 'yd-checkout'),
            'MR' => __('Mauritania', 'yd-checkout'),
            'MU' => __('Mauritius', 'yd-checkout'),
            'YT' => __('Mayotte', 'yd-checkout'),
            'MX' => __('Mexico', 'yd-checkout'),
            'FM' => __('Micronesia, Federated States of', 'yd-checkout'),
            'MD' => __('Moldova, Republic of', 'yd-checkout'),
            'MC' => __('Monaco', 'yd-checkout'),
            'MN' => __('Mongolia', 'yd-checkout'),
            'ME' => __('Montenegro', 'yd-checkout'),
            'MS' => __('Montserrat', 'yd-checkout'),
            'MA' => __('Morocco', 'yd-checkout'),
            'MZ' => __('Mozambique', 'yd-checkout'),
            'MM' => __('Myanmar', 'yd-checkout'),
            'NA' => __('Namibia', 'yd-checkout'),
            'NR' => __('Nauru', 'yd-checkout'),
            'NP' => __('Nepal', 'yd-checkout'),
            'NL' => __('Netherlands', 'yd-checkout'),
            'NC' => __('New Caledonia', 'yd-checkout'),
            'NZ' => __('New Zealand', 'yd-checkout'),
            'NI' => __('Nicaragua', 'yd-checkout'),
            'NE' => __('Niger', 'yd-checkout'),
            'NG' => __('Nigeria', 'yd-checkout'),
            'NU' => __('Niue', 'yd-checkout'),
            'NF' => __('Norfolk Island', 'yd-checkout'),
            'MP' => __('Northern Mariana Islands', 'yd-checkout'),
            'NO' => __('Norway', 'yd-checkout'),
            'OM' => __('Oman', 'yd-checkout'),
            'PK' => __('Pakistan', 'yd-checkout'),
            'PW' => __('Palau', 'yd-checkout'),
            'PS' => __('Palestine, State of', 'yd-checkout'),
            'PA' => __('Panama', 'yd-checkout'),
            'PG' => __('Papua New Guinea', 'yd-checkout'),
            'PY' => __('Paraguay', 'yd-checkout'),
            'PE' => __('Peru', 'yd-checkout'),
            'PH' => __('Philippines', 'yd-checkout'),
            'PN' => __('Pitcairn', 'yd-checkout'),
            'PL' => __('Poland', 'yd-checkout'),
            'PT' => __('Portugal', 'yd-checkout'),
            'PR' => __('Puerto Rico', 'yd-checkout'),
            'QA' => __('Qatar', 'yd-checkout'),
            'RE' => __('Réunion', 'yd-checkout'),
            'RO' => __('Romania', 'yd-checkout'),
            'RU' => __('Russian Federation', 'yd-checkout'),
            'RW' => __('Rwanda', 'yd-checkout'),
            'BL' => __('Saint Barthélemy', 'yd-checkout'),
            'SH' => __('Saint Helena, Ascension and Tristan da Cunha', 'yd-checkout'),
            'KN' => __('Saint Kitts and Nevis', 'yd-checkout'),
            'LC' => __('Saint Lucia', 'yd-checkout'),
            'MF' => __('Saint Martin (French part)', 'yd-checkout'),
            'PM' => __('Saint Pierre and Miquelon', 'yd-checkout'),
            'VC' => __('Saint Vincent and the Grenadines', 'yd-checkout'),
            'WS' => __('Samoa', 'yd-checkout'),
            'SM' => __('San Marino', 'yd-checkout'),
            'ST' => __('Sao Tome and Principe', 'yd-checkout'),
            'SA' => __('Saudi Arabia', 'yd-checkout'),
            'SN' => __('Senegal', 'yd-checkout'),
            'RS' => __('Serbia', 'yd-checkout'),
            'SC' => __('Seychelles', 'yd-checkout'),
            'SL' => __('Sierra Leone', 'yd-checkout'),
            'SG' => __('Singapore', 'yd-checkout'),
            'SX' => __('Sint Maarten (Dutch part)', 'yd-checkout'),
            'SK' => __('Slovakia', 'yd-checkout'),
            'SI' => __('Slovenia', 'yd-checkout'),
            'SB' => __('Solomon Islands', 'yd-checkout'),
            'SO' => __('Somalia', 'yd-checkout'),
            'ZA' => __('South Africa', 'yd-checkout'),
            'GS' => __('South Georgia and the South Sandwich Islands', 'yd-checkout'),
            'SS' => __('South Sudan', 'yd-checkout'),
            'ES' => __('Spain', 'yd-checkout'),
            'LK' => __('Sri Lanka', 'yd-checkout'),
            'SD' => __('Sudan', 'yd-checkout'),
            'SR' => __('Suriname', 'yd-checkout'),
            'SJ' => __('Svalbard and Jan Mayen', 'yd-checkout'),
            'SZ' => __('Swaziland', 'yd-checkout'),
            'SE' => __('Sweden', 'yd-checkout'),
            'CH' => __('Switzerland', 'yd-checkout'),
            'SY' => __('Syrian Arab Republic', 'yd-checkout'),
            'TW' => __('Taiwan', 'yd-checkout'),
            'TJ' => __('Tajikistan', 'yd-checkout'),
            'TZ' => __('Tanzania, United Republic of', 'yd-checkout'),
            'TH' => __('Thailand', 'yd-checkout'),
            'TL' => __('Timor-Leste', 'yd-checkout'),
            'TG' => __('Togo', 'yd-checkout'),
            'TK' => __('Tokelau', 'yd-checkout'),
            'TO' => __('Tonga', 'yd-checkout'),
            'TT' => __('Trinidad and Tobago', 'yd-checkout'),
            'TN' => __('Tunisia', 'yd-checkout'),
            'TR' => __('Turkey', 'yd-checkout'),
            'TM' => __('Turkmenistan', 'yd-checkout'),
            'TC' => __('Turks and Caicos Islands', 'yd-checkout'),
            'TV' => __('Tuvalu', 'yd-checkout'),
            'UG' => __('Uganda', 'yd-checkout'),
            'UA' => __('Ukraine', 'yd-checkout'),
            'AE' => __('United Arab Emirates', 'yd-checkout'),
            'GB' => __('United Kingdom', 'yd-checkout'),
            'US' => __('United States', 'yd-checkout'),
            'UM' => __('United States Minor Outlying Islands', 'yd-checkout'),
            'UY' => __('Uruguay', 'yd-checkout'),
            'UZ' => __('Uzbekistan', 'yd-checkout'),
            'VU' => __('Vanuatu', 'yd-checkout'),
            'VE' => __('Venezuela, Bolivarian Republic of', 'yd-checkout'),
            'VN' => __('Viet Nam', 'yd-checkout'),
            'VG' => __('Virgin Islands, British', 'yd-checkout'),
            'VI' => __('Virgin Islands, U.S.', 'yd-checkout'),
            'WF' => __('Wallis and Futuna', 'yd-checkout'),
            'EH' => __('Western Sahara', 'yd-checkout'),
            'YE' => __('Yemen', 'yd-checkout'),
            'ZM' => __('Zambia', 'yd-checkout'),
            'ZW' => __('Zimbabwe', 'yd-checkout'),
        );
    }

    /**
     * Get country name from ISO code
     *
     * @param string $country_code The country code
     * @return string|false The country name or false if not found
     */
    public static function get_country_name($country_code) {
        $countries = self::get_countries();
        return isset($countries[$country_code]) ? $countries[$country_code] : false;
    }

    /**
     * Get country code from name
     *
     * @param string $country_name The country name
     * @return string|false The country code or false if not found
     */
    public static function get_country_code($country_name) {
        $countries = self::get_countries();
        $country_name = trim($country_name);
        
        // Direct match
        $code = array_search($country_name, $countries);
        if ($code !== false) {
            return $code;
        }
        
        // Case insensitive match
        foreach ($countries as $code => $name) {
            if (strtolower($name) === strtolower($country_name)) {
                return $code;
            }
        }
        
        return false;
    }

    /**
     * Map of ISO 3166-1 alpha-3 codes to ISO 3166-1 alpha-2 codes
     *
     * @return array Associative array of ISO3 => ISO2 codes
     */
    public static function get_iso3_to_iso2_map() {
        return array(
            'AFG' => 'AF', // Afghanistan
            'ALB' => 'AL', // Albania
            'DZA' => 'DZ', // Algeria
            'ASM' => 'AS', // American Samoa
            'AND' => 'AD', // Andorra
            'AGO' => 'AO', // Angola
            'AIA' => 'AI', // Anguilla
            'ATA' => 'AQ', // Antarctica
            'ATG' => 'AG', // Antigua and Barbuda
            'ARG' => 'AR', // Argentina
            'ARM' => 'AM', // Armenia
            'ABW' => 'AW', // Aruba
            'AUS' => 'AU', // Australia
            'AUT' => 'AT', // Austria
            'AZE' => 'AZ', // Azerbaijan
            'BHS' => 'BS', // Bahamas
            'BHR' => 'BH', // Bahrain
            'BGD' => 'BD', // Bangladesh
            'BRB' => 'BB', // Barbados
            'BLR' => 'BY', // Belarus
            'BEL' => 'BE', // Belgium
            'BLZ' => 'BZ', // Belize
            'BEN' => 'BJ', // Benin
            'BMU' => 'BM', // Bermuda
            'BTN' => 'BT', // Bhutan
            'BOL' => 'BO', // Bolivia
            'BIH' => 'BA', // Bosnia and Herzegovina
            'BWA' => 'BW', // Botswana
            'BVT' => 'BV', // Bouvet Island
            'BRA' => 'BR', // Brazil
            'IOT' => 'IO', // British Indian Ocean Territory
            'BRN' => 'BN', // Brunei Darussalam
            'BGR' => 'BG', // Bulgaria
            'BFA' => 'BF', // Burkina Faso
            'BDI' => 'BI', // Burundi
            'KHM' => 'KH', // Cambodia
            'CMR' => 'CM', // Cameroon
            'CAN' => 'CA', // Canada
            'CPV' => 'CV', // Cape Verde
            'CYM' => 'KY', // Cayman Islands
            'CAF' => 'CF', // Central African Republic
            'TCD' => 'TD', // Chad
            'CHL' => 'CL', // Chile
            'CHN' => 'CN', // China
            'CXR' => 'CX', // Christmas Island
            'CCK' => 'CC', // Cocos (Keeling) Islands
            'COL' => 'CO', // Colombia
            'COM' => 'KM', // Comoros
            'COG' => 'CG', // Congo
            'COD' => 'CD', // Congo, the Democratic Republic of the
            'COK' => 'CK', // Cook Islands
            'CRI' => 'CR', // Costa Rica
            'CIV' => 'CI', // Cote D'Ivoire
            'HRV' => 'HR', // Croatia
            'CUB' => 'CU', // Cuba
            'CYP' => 'CY', // Cyprus
            'CZE' => 'CZ', // Czech Republic
            'DNK' => 'DK', // Denmark
            'DJI' => 'DJ', // Djibouti
            'DMA' => 'DM', // Dominica
            'DOM' => 'DO', // Dominican Republic
            'ECU' => 'EC', // Ecuador
            'EGY' => 'EG', // Egypt
            'SLV' => 'SV', // El Salvador
            'GNQ' => 'GQ', // Equatorial Guinea
            'ERI' => 'ER', // Eritrea
            'EST' => 'EE', // Estonia
            'ETH' => 'ET', // Ethiopia
            'FLK' => 'FK', // Falkland Islands (Malvinas)
            'FRO' => 'FO', // Faroe Islands
            'FJI' => 'FJ', // Fiji
            'FIN' => 'FI', // Finland
            'FRA' => 'FR', // France
            'GUF' => 'GF', // French Guiana
            'PYF' => 'PF', // French Polynesia
            'ATF' => 'TF', // French Southern Territories
            'GAB' => 'GA', // Gabon
            'GMB' => 'GM', // Gambia
            'GEO' => 'GE', // Georgia
            'DEU' => 'DE', // Germany
            'GHA' => 'GH', // Ghana
            'GIB' => 'GI', // Gibraltar
            'GRC' => 'GR', // Greece
            'GRL' => 'GL', // Greenland
            'GRD' => 'GD', // Grenada
            'GLP' => 'GP', // Guadeloupe
            'GUM' => 'GU', // Guam
            'GTM' => 'GT', // Guatemala
            'GGY' => 'GG', // Guernsey
            'GIN' => 'GN', // Guinea
            'GNB' => 'GW', // Guinea-Bissau
            'GUY' => 'GY', // Guyana
            'HTI' => 'HT', // Haiti
            'HMD' => 'HM', // Heard Island and Mcdonald Islands
            'VAT' => 'VA', // Holy See (Vatican City State)
            'HND' => 'HN', // Honduras
            'HKG' => 'HK', // Hong Kong
            'HUN' => 'HU', // Hungary
            'ISL' => 'IS', // Iceland
            'IND' => 'IN', // India
            'IDN' => 'ID', // Indonesia
            'IRN' => 'IR', // Iran, Islamic Republic of
            'IRQ' => 'IQ', // Iraq
            'IRL' => 'IE', // Ireland
            'IMN' => 'IM', // Isle of Man
            'ISR' => 'IL', // Israel
            'ITA' => 'IT', // Italy
            'JAM' => 'JM', // Jamaica
            'JPN' => 'JP', // Japan
            'JEY' => 'JE', // Jersey
            'JOR' => 'JO', // Jordan
            'KAZ' => 'KZ', // Kazakhstan
            'KEN' => 'KE', // Kenya
            'KIR' => 'KI', // Kiribati
            'PRK' => 'KP', // Korea, Democratic People's Republic of
            'KOR' => 'KR', // Korea, Republic of
            'KWT' => 'KW', // Kuwait
            'KGZ' => 'KG', // Kyrgyzstan
            'LAO' => 'LA', // Lao People's Democratic Republic
            'LVA' => 'LV', // Latvia
            'LBN' => 'LB', // Lebanon
            'LSO' => 'LS', // Lesotho
            'LBR' => 'LR', // Liberia
            'LBY' => 'LY', // Libyan Arab Jamahiriya
            'LIE' => 'LI', // Liechtenstein
            'LTU' => 'LT', // Lithuania
            'LUX' => 'LU', // Luxembourg
            'MAC' => 'MO', // Macao
            'MKD' => 'MK', // Macedonia, the Former Yugoslav Republic of
            'MDG' => 'MG', // Madagascar
            'MWI' => 'MW', // Malawi
            'MYS' => 'MY', // Malaysia
            'MDV' => 'MV', // Maldives
            'MLI' => 'ML', // Mali
            'MLT' => 'MT', // Malta
            'MHL' => 'MH', // Marshall Islands
            'MTQ' => 'MQ', // Martinique
            'MRT' => 'MR', // Mauritania
            'MUS' => 'MU', // Mauritius
            'MYT' => 'YT', // Mayotte
            'MEX' => 'MX', // Mexico
            'FSM' => 'FM', // Micronesia, Federated States of
            'MDA' => 'MD', // Moldova, Republic of
            'MCO' => 'MC', // Monaco
            'MNG' => 'MN', // Mongolia
            'MNE' => 'ME', // Montenegro
            'MSR' => 'MS', // Montserrat
            'MAR' => 'MA', // Morocco
            'MOZ' => 'MZ', // Mozambique
            'MMR' => 'MM', // Myanmar
            'NAM' => 'NA', // Namibia
            'NRU' => 'NR', // Nauru
            'NPL' => 'NP', // Nepal
            'NLD' => 'NL', // Netherlands
            'ANT' => 'AN', // Netherlands Antilles
            'NCL' => 'NC', // New Caledonia
            'NZL' => 'NZ', // New Zealand
            'NIC' => 'NI', // Nicaragua
            'NER' => 'NE', // Niger
            'NGA' => 'NG', // Nigeria
            'NIU' => 'NU', // Niue
            'NFK' => 'NF', // Norfolk Island
            'MNP' => 'MP', // Northern Mariana Islands
            'NOR' => 'NO', // Norway
            'OMN' => 'OM', // Oman
            'PAK' => 'PK', // Pakistan
            'PLW' => 'PW', // Palau
            'PSE' => 'PS', // Palestinian Territory, Occupied
            'PAN' => 'PA', // Panama
            'PNG' => 'PG', // Papua New Guinea
            'PRY' => 'PY', // Paraguay
            'PER' => 'PE', // Peru
            'PHL' => 'PH', // Philippines
            'PCN' => 'PN', // Pitcairn
            'POL' => 'PL', // Poland
            'PRT' => 'PT', // Portugal
            'PRI' => 'PR', // Puerto Rico
            'QAT' => 'QA', // Qatar
            'REU' => 'RE', // Reunion
            'ROU' => 'RO', // Romania
            'RUS' => 'RU', // Russian Federation
            'RWA' => 'RW', // Rwanda
            'BLM' => 'BL', // Saint Barthelemy
            'SHN' => 'SH', // Saint Helena
            'KNA' => 'KN', // Saint Kitts and Nevis
            'LCA' => 'LC', // Saint Lucia
            'MAF' => 'MF', // Saint Martin
            'SPM' => 'PM', // Saint Pierre and Miquelon
            'VCT' => 'VC', // Saint Vincent and the Grenadines
            'WSM' => 'WS', // Samoa
            'SMR' => 'SM', // San Marino
            'STP' => 'ST', // Sao Tome and Principe
            'SAU' => 'SA', // Saudi Arabia
            'SEN' => 'SN', // Senegal
            'SRB' => 'RS', // Serbia
            'SYC' => 'SC', // Seychelles
            'SLE' => 'SL', // Sierra Leone
            'SGP' => 'SG', // Singapore
            'SVK' => 'SK', // Slovakia
            'SVN' => 'SI', // Slovenia
            'SLB' => 'SB', // Solomon Islands
            'SOM' => 'SO', // Somalia
            'ZAF' => 'ZA', // South Africa
            'SGS' => 'GS', // South Georgia and the South Sandwich Islands
            'SSD' => 'SS', // South Sudan
            'ESP' => 'ES', // Spain
            'LKA' => 'LK', // Sri Lanka
            'SDN' => 'SD', // Sudan
            'SUR' => 'SR', // Suriname
            'SJM' => 'SJ', // Svalbard and Jan Mayen
            'SWZ' => 'SZ', // Swaziland
            'SWE' => 'SE', // Sweden
            'CHE' => 'CH', // Switzerland
            'SYR' => 'SY', // Syrian Arab Republic
            'TWN' => 'TW', // Taiwan, Province of China
            'TJK' => 'TJ', // Tajikistan
            'TZA' => 'TZ', // Tanzania, United Republic of
            'THA' => 'TH', // Thailand
            'TLS' => 'TL', // Timor-Leste
            'TGO' => 'TG', // Togo
            'TKL' => 'TK', // Tokelau
            'TON' => 'TO', // Tonga
            'TTO' => 'TT', // Trinidad and Tobago
            'TUN' => 'TN', // Tunisia
            'TUR' => 'TR', // Turkey
            'TKM' => 'TM', // Turkmenistan
            'TCA' => 'TC', // Turks and Caicos Islands
            'TUV' => 'TV', // Tuvalu
            'UGA' => 'UG', // Uganda
            'UKR' => 'UA', // Ukraine
            'ARE' => 'AE', // United Arab Emirates
            'GBR' => 'GB', // United Kingdom
            'USA' => 'US', // United States
            'UMI' => 'UM', // United States Minor Outlying Islands
            'URY' => 'UY', // Uruguay
            'UZB' => 'UZ', // Uzbekistan
            'VUT' => 'VU', // Vanuatu
            'VEN' => 'VE', // Venezuela
            'VNM' => 'VN', // Viet Nam
            'VGB' => 'VG', // Virgin Islands, British
            'VIR' => 'VI', // Virgin Islands, U.S.
            'WLF' => 'WF', // Wallis and Futuna
            'ESH' => 'EH', // Western Sahara
            'YEM' => 'YE', // Yemen
            'ZMB' => 'ZM', // Zambia
            'ZWE' => 'ZW', // Zimbabwe
        );
    }

    /**
     * Convert ISO 3166-1 alpha-3 country code to ISO 3166-1 alpha-2
     *
     * @param string $iso3_code The ISO3 country code
     * @return string|false The ISO2 country code or false if not found
     */
    public static function convert_iso3_to_iso2($iso3_code) {
        $iso3_code = strtoupper(trim($iso3_code));
        $map = self::get_iso3_to_iso2_map();
        
        return isset($map[$iso3_code]) ? $map[$iso3_code] : false;
    }
}