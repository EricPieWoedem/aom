/**
 * Countries, currencies, and PawaPay correspondent codes (aligned with PHP demo form).
 * Sandbox: enable matching countries in the PawaPay dashboard; see Active Configuration API.
 */
window.PAWAPAY_COUNTRY_LABELS = {
  BEN: 'Benin',
  BFA: 'Burkina Faso',
  CMR: 'Cameroon',
  CIV: "Côte d'Ivoire",
  COD: 'Democratic Republic of the Congo',
  GAB: 'Gabon',
  GHA: 'Ghana',
  KEN: 'Kenya',
  MWI: 'Malawi',
  MOZ: 'Mozambique',
  NGA: 'Nigeria',
  COG: 'Republic of the Congo',
  RWA: 'Rwanda',
  SEN: 'Senegal',
  SLE: 'Sierra Leone',
  TZA: 'Tanzania',
  UGA: 'Uganda',
  ZMB: 'Zambia'
};

window.PAWAPAY_MARKETS = {
  BEN: {
    operators: [
      { value: 'MTN_MOMO_BEN', label: 'MTN Benin' },
      { value: 'MOOV_BEN', label: 'Moov Benin' }
    ],
    currencies: ['XOF']
  },
  BFA: {
    operators: [
      { value: 'MOOV_BFA', label: 'Moov Burkina Faso' },
      { value: 'ORANGE_BFA', label: 'Orange Burkina Faso' }
    ],
    currencies: ['XOF']
  },
  CMR: {
    operators: [
      { value: 'MTN_MOMO_CMR', label: 'MTN Cameroon' },
      { value: 'ORANGE_CMR', label: 'Orange Cameroon' }
    ],
    currencies: ['XAF']
  },
  CIV: {
    operators: [
      { value: 'MTN_MOMO_CIV', label: "MTN Côte d'Ivoire" },
      { value: 'ORANGE_CIV', label: "Orange Côte d'Ivoire" }
    ],
    currencies: ['XOF']
  },
  COD: {
    operators: [
      { value: 'VODACOM_MPESA_COD', label: 'M-Pesa DRC' },
      { value: 'AIRTEL_COD', label: 'Airtel DRC' },
      { value: 'ORANGE_COD', label: 'Orange DRC' }
    ],
    currencies: ['CDF', 'USD']
  },
  GAB: {
    operators: [{ value: 'AIRTEL_GAB', label: 'Airtel Gabon' }],
    currencies: ['XAF']
  },
  GHA: {
    operators: [
      { value: 'MTN_MOMO_GHA', label: 'MTN Ghana' },
      { value: 'AIRTELTIGO_GHA', label: 'AirtelTigo Ghana' },
      { value: 'VODAFONE_GHA', label: 'Telecel Ghana' }
    ],
    currencies: ['GHS']
  },
  KEN: {
    operators: [{ value: 'MPESA_KEN', label: 'Mpesa Kenya' }],
    currencies: ['KES']
  },
  MWI: {
    operators: [
      { value: 'AIRTEL_MWI', label: 'Airtel Malawi' },
      { value: 'TNM_MWI', label: 'TNM Malawi' }
    ],
    currencies: ['MWK']
  },
  MOZ: {
    operators: [{ value: 'VODACOM_MOZ', label: 'Vodacom Mozambique' }],
    currencies: ['MZN']
  },
  NGA: {
    operators: [
      { value: 'AIRTEL_NGA', label: 'Airtel Nigeria' },
      { value: 'MTN_MOMO_NGA', label: 'MTN Nigeria' }
    ],
    currencies: ['NGN']
  },
  COG: {
    operators: [
      { value: 'AIRTEL_COG', label: 'Airtel Congo' },
      { value: 'MTN_MOMO_COG', label: 'MTN Congo' }
    ],
    currencies: ['XAF']
  },
  RWA: {
    operators: [
      { value: 'AIRTEL_RWA', label: 'Airtel Rwanda' },
      { value: 'MTN_MOMO_RWA', label: 'MTN Rwanda' }
    ],
    currencies: ['RWF']
  },
  SEN: {
    operators: [
      { value: 'FREE_SEN', label: 'Free Senegal' },
      { value: 'ORANGE_SEN', label: 'Orange Senegal' }
    ],
    currencies: ['XOF']
  },
  SLE: {
    operators: [{ value: 'ORANGE_SLE', label: 'Orange Sierra Leone' }],
    currencies: ['SLE']
  },
  TZA: {
    operators: [
      { value: 'AIRTEL_TZA', label: 'Airtel Tanzania' },
      { value: 'VODACOM_TZA', label: 'Vodacom Tanzania' },
      { value: 'TIGO_TZA', label: 'Tigo Tanzania' },
      { value: 'HALOTEL_TZA', label: 'Halotel Tanzania' }
    ],
    currencies: ['TZS']
  },
  UGA: {
    operators: [
      { value: 'AIRTEL_OAPI_UGA', label: 'Airtel Uganda' },
      { value: 'MTN_MOMO_UGA', label: 'MTN Uganda' }
    ],
    currencies: ['UGX']
  },
  ZMB: {
    operators: [
      { value: 'AIRTEL_OAPI_ZMB', label: 'Airtel Zambia' },
      { value: 'MTN_MOMO_ZMB', label: 'MTN Zambia' },
      { value: 'ZAMTEL_ZMB', label: 'Zamtel Zambia' }
    ],
    currencies: ['ZMW']
  }
};
