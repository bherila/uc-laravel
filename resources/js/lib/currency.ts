/**
 * Format a number as currency (USD)
 */
export function formatCurrency(value: number, digits: number = 2): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  }).format(value);
}

/**
 * Parse a currency string to number
 */
export function parseCurrency(value: string): number {
  return parseFloat(value.replace(/[$,]/g, '')) || 0;
}
