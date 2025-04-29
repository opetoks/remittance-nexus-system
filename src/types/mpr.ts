
/**
 * Represents daily income data for a specific income line
 */
export interface DailyIncomeData {
  [day: number]: number;
}

/**
 * Represents an income line with daily collection data
 */
export interface IncomeData {
  incomeLine: string;
  days: DailyIncomeData;
  total: number;
}

/**
 * Represents the structure of the MPR summary API response
 */
export interface MPRSummaryResponse {
  data: IncomeData[];
  period: {
    month: string;
    year: string;
  };
  totals: {
    [day: number]: number;
    grandTotal: number;
  };
}

/**
 * Officer-specific MPR data interface (to be expanded later)
 */
export interface OfficerMPRData {
  officerId: string;
  officerName: string;
  collections: {
    incomeLine: string;
    amount: number;
  }[];
  totalCollected: number;
}
