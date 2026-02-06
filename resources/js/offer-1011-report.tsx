import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect } from 'react';
import Container from '@/components/container';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { fetchWrapper } from '@/fetchWrapper';
import { Download, ArrowLeft } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface ReportRow {
  'Version': string;
  'Company': string;
  'Sales Order Key': string;
  'Fulfillment Account Key': string;
  'Shipment Key': string;
  'Club Type': string;
  'Sub Club': string;
  'Pickup': string;
  '3 Tier': string;
  'Tags': string;
  'Reserved 1': string;
  'Reserved 2': string;
  'Reserved 3': string;
  'Billing Last Name': string;
  'Billing First Name': string;
  'Billing Company': string;
  'Billing Address 1': string;
  'Billing Address 2': string;
  'Billing City': string;
  'Billing State': string;
  'Billing Zip': string;
  'Billing Date Of Birth': string;
  'Billing Email': string;
  'Billing Phone': string;
  'Sales Type': string;
  'Order Type': string;
  'Customer Number': string;
  'Payment Date': string;
  'Shipping Last Name': string;
  'Shipping First Name': string;
  'Shipping Company': string;
  'Shipping Address 1': string;
  'Shipping Address 2': string;
  'Shipping City': string;
  'Shipping State': string;
  'Shipping Zip': string;
  'Shipping County': string;
  'Shipping Date Of Birth': string;
  'Shipping Email': string;
  'Shipping Phone': string;
  'Carrier Service': string;
  'Ship Date': string;
  'Freight Cost': string;
  'Tracking Number': string;
  'Sample Type': string;
  'Age Check ID': string;
  'Discount Amount': string;
  'Discount %': string;
  'RDBI': string;
  'Compliant': string;
  'Compliance Results': string;
  'Fulfillment House': string;
  'Shipment Status': string;
  'License Relationship': string;
  'Insured Amount': string;
  'Sales Tax Charged': string;
  'Handling Fees': string;
  'Gift Note': string;
  'Special Instructions': string;
  'Brand Key': string;
  'Product Key': string;
  'Quantity': string;
  'Unit Price': string;
  'Weight': string;
}

interface ReportData {
  offer_id: number;
  offer_name: string;
  variant_id: string;
  shop_id: number;
  shop?: {
    id: number;
    name: string;
    shop_domain: string;
  };
  rows: ReportRow[];
}

function Report1011Page() {
  const [data, setData] = useState<ReportData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const root = document.getElementById('offer-1011-report-root');
  const offerId = root?.dataset.offerId;
  const shopId = root?.dataset.shopId;
  const offerName = root?.dataset.offerName || '';
  const apiBase = root?.dataset.apiBase || '/api';

  useEffect(() => {
    const loadData = async () => {
      try {
        const reportData = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}/1011-report`);
        setData(reportData);
      } catch (err: any) {
        console.error('Failed to load 1011 report:', err);
        setError(err?.error || 'Failed to load report');
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [apiBase, shopId, offerId]);

  const downloadCSV = () => {
    if (!data || data.rows.length === 0 || !data.rows[0]) return;

    // Get column headers from first row
    const headers = Object.keys(data.rows[0]) as (keyof ReportRow)[];
    
    // Helper function to escape and quote CSV values
    const escapeCSVValue = (value: string): string => {
      // Convert to string and handle null/undefined
      const str = String(value ?? '');
      // If value contains comma, quote, or newline, wrap in quotes and escape quotes
      if (str.includes(',') || str.includes('"') || str.includes('\n')) {
        return `"${str.replace(/"/g, '""')}"`;
      }
      return str;
    };

    // Build CSV content
    const csvLines = [
      headers.join(','),
      ...data.rows.map(row => 
        headers.map(header => escapeCSVValue(row[header])).join(',')
      )
    ];

    const csvContent = csvLines.join('\n');

    // Create blob and download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `1011_report_offer_${offerId}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  if (loading) {
    return (
      <Container>
        <ShopOfferBreadcrumb
          shopId={shopId!}
          shopName={data?.shop?.name}
          offer={offerId ? { id: offerId, name: offerName } : undefined}
          action="1011 Report"
        />
        <div className="space-y-4 mt-6">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-64 w-full" />
        </div>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <ShopOfferBreadcrumb
          shopId={shopId!}
          shopName={data?.shop?.name}
          offer={offerId ? { id: offerId, name: offerName } : undefined}
          action="1011 Report"
        />
        <Alert variant="destructive" className="mt-6">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      </Container>
    );
  }

  if (!data) {
    return null;
  }

  const columnHeaders: (keyof ReportRow)[] = [
    'Version',
    'Company',
    'Sales Order Key',
    'Fulfillment Account Key',
    'Shipment Key',
    'Club Type',
    'Sub Club',
    'Pickup',
    '3 Tier',
    'Tags',
    'Reserved 1',
    'Reserved 2',
    'Reserved 3',
    'Billing Last Name',
    'Billing First Name',
    'Billing Company',
    'Billing Address 1',
    'Billing Address 2',
    'Billing City',
    'Billing State',
    'Billing Zip',
    'Billing Date Of Birth',
    'Billing Email',
    'Billing Phone',
    'Sales Type',
    'Order Type',
    'Customer Number',
    'Payment Date',
    'Shipping Last Name',
    'Shipping First Name',
    'Shipping Company',
    'Shipping Address 1',
    'Shipping Address 2',
    'Shipping City',
    'Shipping State',
    'Shipping Zip',
    'Shipping County',
    'Shipping Date Of Birth',
    'Shipping Email',
    'Shipping Phone',
    'Carrier Service',
    'Ship Date',
    'Freight Cost',
    'Tracking Number',
    'Sample Type',
    'Age Check ID',
    'Discount Amount',
    'Discount %',
    'RDBI',
    'Compliant',
    'Compliance Results',
    'Fulfillment House',
    'Shipment Status',
    'License Relationship',
    'Insured Amount',
    'Sales Tax Charged',
    'Handling Fees',
    'Gift Note',
    'Special Instructions',
    'Brand Key',
    'Product Key',
    'Quantity',
    'Unit Price',
    'Weight',
  ];

  return (
    <Container>
      <div className="mb-6">
        <ShopOfferBreadcrumb
          shopId={shopId!}
          shopName={data?.shop?.name}
          offer={offerId ? { id: offerId, name: offerName } : undefined}
          action="1011 Report"
        />
      </div>

      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold">1011 Report</h1>
          <p className="text-sm text-muted-foreground mt-1">
            {data.rows.length} {data.rows.length === 1 ? 'row' : 'rows'}
          </p>
        </div>
        <Button onClick={downloadCSV} disabled={data.rows.length === 0}>
          <Download className="h-4 w-4 mr-2" />
          Download CSV
        </Button>
      </div>

      {data.rows.length === 0 ? (
        <Alert>
          <AlertDescription>No orders found for this offer.</AlertDescription>
        </Alert>
      ) : (
        <div className="border rounded-lg overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                {columnHeaders.map(header => (
                  <TableHead key={header} className="whitespace-nowrap min-w-[150px]">
                    {header}
                  </TableHead>
                ))}
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.rows.map((row, idx) => (
                <TableRow key={idx}>
                  {columnHeaders.map(header => (
                    <TableCell key={header} className="whitespace-nowrap">
                      {row[header]}
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}
    </Container>
  );
}

const root = document.getElementById('offer-1011-report-root');
if (root) {
  createRoot(root).render(<Report1011Page />);
}
