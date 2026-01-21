import React, { useState, useRef } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { FileUp, Clipboard, Loader2, AlertCircle, X, CheckCircle, XCircle } from 'lucide-react';
import { toast } from 'sonner';
import { splitDelimitedText } from '@/lib/splitDelimitedText';
import { fetchWrapper } from '@/fetchWrapper';

interface ImportItem {
  sku: string;
  qty: number;
  productName?: string;
  isValid?: boolean;
  isVerifying?: boolean;
  error?: string;
}

interface OfferImportCsvButtonProps {
  shopId: string | number;
  offerId: string | number;
  onImportSuccess: () => void;
}

export function OfferImportCsvButton({
  shopId,
  offerId,
  onImportSuccess,
}: OfferImportCsvButtonProps) {
  const [open, setOpen] = useState(false);
  const [importData, setImportData] = useState<ImportItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [validating, setValidating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const validateSkus = async (items: ImportItem[]) => {
    setValidating(true);
    try {
      const skus = Array.from(new Set(items.map(item => item.sku)));
      const apiBase = document.getElementById('offer-detail-root')?.dataset.apiBase || '/api';
      
      const validationResults = await fetchWrapper.put(
        `${apiBase}/shops/${shopId}/offers/${offerId}/manifests/import`,
        { skus }
      );

      setImportData(prev => prev.map(item => {
        const result = validationResults[item.sku];
        if (result) {
          return {
            ...item,
            isValid: result.valid,
            productName: result.productName,
            error: result.error,
          };
        }
        return item;
      }));
    } catch (err) {
      console.error('Validation failed', err);
      toast.error('Failed to validate SKUs with Shopify');
    } finally {
      setValidating(false);
    }
  };

  const parseCsv = async (text: string) => {
    try {
      setError(null);
      const rows = splitDelimitedText(text, ',');
      if (!rows || rows.length === 0) return;

      let startIndex = 0;
      let skuIdx = 0;
      let qtyIdx = 1;

      const firstRow = rows[0];
      if (!firstRow) return;

      const firstRowLower = firstRow.map(c => c.toLowerCase().trim());
      const hasHeader = firstRowLower.some(c => 
        c.includes('sku') || 
        c.includes('variant') || 
        c.includes('offered') || 
        c.includes('qty') ||
        c.includes('product')
      );

      if (hasHeader) {
        startIndex = 1;
        // Try to find indices with priority
        const sIdx = firstRowLower.indexOf('sku');
        const vIdx = firstRowLower.indexOf('variant id');
        const qIdx = firstRowLower.findIndex(c => 
            c === 'num offered' || 
            c === 'qty' || 
            c === 'quantity' || 
            c.includes('offered')
        );
        
        if (sIdx !== -1) {
            skuIdx = sIdx;
        } else if (vIdx !== -1) {
            skuIdx = vIdx;
        }
        
        if (qIdx !== -1) qtyIdx = qIdx;
      }

      const items: ImportItem[] = [];
      for (let i = startIndex; i < rows.length; i++) {
        const row = rows[i];
        if (!row || row.length < 1) continue;
        
        const sku = row[skuIdx]?.trim();
        const qtyStr = row[qtyIdx]?.trim() || '1';
        const qty = parseInt(qtyStr, 10);

        if (sku && !isNaN(qty)) {
          items.push({ sku, qty, isVerifying: true });
        }
      }

      if (items.length === 0) {
        setError('No valid data found in CSV. Expected columns: SKU, Quantity.');
      } else {
        setImportData(items);
        await validateSkus(items);
      }
    } catch (err) {
      console.error(err);
      setError('Failed to parse CSV');
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      const text = event.target?.result as string;
      parseCsv(text);
    };
    reader.readAsText(file);
  };

  const handlePaste = async () => {
    try {
      const text = await navigator.clipboard.readText();
      parseCsv(text);
    } catch (err) {
      toast.error('Failed to read from clipboard');
    }
  };

  const handleImport = async () => {
    setLoading(true);
    setError(null);
    try {
      const apiBase = document.getElementById('offer-detail-root')?.dataset.apiBase || '/api';
      await fetchWrapper.post(`${apiBase}/shops/${shopId}/offers/${offerId}/manifests/import`, {
        items: importData.filter(i => i.isValid).map(i => ({ sku: i.sku, qty: i.qty })),
      });
      toast.success(`Successfully imported ${importData.filter(i => i.isValid).length} items`);
      setOpen(false);
      setImportData([]);
      onImportSuccess();
    } catch (err: any) {
      setError(err?.error || 'Import failed');
      if (err?.details) {
        setError(`${err.error}: ${err.details.slice(0, 3).join(', ')}${err.details.length > 3 ? '...' : ''}`);
      }
    } finally {
      setLoading(false);
    }
  };

  const reset = () => {
    setImportData([]);
    setError(null);
  };

  const allValid = importData.length > 0 && importData.every(i => i.isValid);
  const someValid = importData.some(i => i.isValid);

  return (
    <Dialog open={open} onOpenChange={(val) => {
        setOpen(val);
        if (!val) reset();
    }}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <FileUp className="w-4 h-4 mr-2" />
          Import CSV
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-3xl max-h-[90vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>Import Manifest Items (CSV)</DialogTitle>
        </DialogHeader>
        
        <div className="flex-1 overflow-y-auto py-4">
          {importData.length === 0 ? (
            <div 
              className="border-2 border-dashed rounded-lg p-12 text-center space-y-4 hover:bg-muted/50 transition-colors cursor-pointer"
              onClick={() => fileInputRef.current?.click()}
              onDragOver={(e) => e.preventDefault()}
              onDrop={(e) => {
                e.preventDefault();
                const file = e.dataTransfer.files?.[0];
                if (file) {
                  const reader = new FileReader();
                  reader.onload = (event) => parseCsv(event.target?.result as string);
                  reader.readAsText(file);
                }
              }}
            >
              <div className="flex justify-center">
                <FileUp className="w-12 h-12 text-muted-foreground" />
              </div>
              <div>
                <p className="text-lg font-medium">Click to upload or drag & drop</p>
                <p className="text-sm text-muted-foreground">CSV files with SKU and Quantity columns</p>
              </div>
              <div className="flex justify-center gap-4 pt-4">
                <Button variant="secondary" onClick={(e) => { e.stopPropagation(); handlePaste(); }}>
                  <Clipboard className="w-4 h-4 mr-2" />
                  Paste from Clipboard
                </Button>
              </div>
              <input 
                type="file" 
                ref={fileInputRef} 
                onChange={handleFileUpload} 
                accept=".csv" 
                className="hidden" 
              />
            </div>
          ) : (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <h3 className="font-medium">{importData.length} items parsed</h3>
                    {validating && <Loader2 className="w-3 h-3 animate-spin text-muted-foreground" />}
                </div>
                <Button variant="ghost" size="sm" onClick={reset}>
                  <X className="w-4 h-4 mr-2" /> Clear
                </Button>
              </div>
              <div className="rounded-md border max-h-96 overflow-y-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-[50px]">Status</TableHead>
                      <TableHead>SKU</TableHead>
                      <TableHead>Product Name</TableHead>
                      <TableHead className="text-right">Quantity</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {importData.map((item, i) => (
                      <TableRow key={i} className={!item.isValid && !validating && item.productName === undefined ? 'bg-destructive/5' : ''}>
                        <TableCell>
                          {validating && !item.productName ? (
                            <Loader2 className="w-4 h-4 animate-spin text-muted-foreground" />
                          ) : item.isValid ? (
                            <CheckCircle className="w-4 h-4 text-green-600" />
                          ) : item.isValid === false ? (
                            <XCircle className="w-4 h-4 text-destructive" />
                          ) : null}
                        </TableCell>
                        <TableCell className="font-mono text-xs">{item.sku}</TableCell>
                        <TableCell className="text-sm">
                            {item.productName || (item.isValid === false ? <span className="text-destructive text-xs">Not found in Shopify</span> : '-')}
                        </TableCell>
                        <TableCell className="text-right font-medium">{item.qty}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </div>
          )}

          {error && (
            <div className="mt-4 p-3 bg-destructive/15 text-destructive rounded-md flex items-start gap-2 text-sm">
              <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
              <span>{error}</span>
            </div>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => setOpen(false)} disabled={loading}>
            Cancel
          </Button>
          <Button 
            onClick={handleImport} 
            disabled={importData.length === 0 || loading || validating || !someValid}
          >
            {loading && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
            {loading ? 'Importing...' : `Import ${importData.filter(i => i.isValid).length} Products`}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}