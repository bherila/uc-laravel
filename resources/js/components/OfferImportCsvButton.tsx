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
import { FileUp, Clipboard, Loader2, AlertCircle, X } from 'lucide-react';
import { toast } from 'sonner';
import { splitDelimitedText } from '@/lib/splitDelimitedText';
import { fetchWrapper } from '@/fetchWrapper';

interface ImportItem {
  sku: string;
  qty: number;
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
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const parseCsv = (text: string) => {
    try {
      setError(null);
      const rows = splitDelimitedText(text, ',');
      if (!rows || rows.length === 0) return;

      let startIndex = 0;
      let skuIdx = 0;
      let qtyIdx = 1;

      // Check for headers in the first row
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
        // Try to find indices
        const sIdx = firstRowLower.findIndex(c => c.includes('sku') || c.includes('variant id'));
        const qIdx = firstRowLower.findIndex(c => c.includes('offered') || c.includes('qty') || c.includes('quantity'));
        
        if (sIdx !== -1) skuIdx = sIdx;
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
          items.push({ sku, qty });
        }
      }

      if (items.length === 0) {
        setError('No valid data found in CSV. Expected columns: SKU, Quantity.');
      } else {
        setImportData(items);
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
        items: importData,
      });
      toast.success(`Successfully imported ${importData.length} items`);
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
      <DialogContent className="max-w-2xl max-h-[90vh] flex flex-col">
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
                <h3 className="font-medium">{importData.length} items ready to import</h3>
                <Button variant="ghost" size="sm" onClick={reset}>
                  <X className="w-4 h-4 mr-2" /> Clear
                </Button>
              </div>
              <div className="rounded-md border max-h-64 overflow-y-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>SKU</TableHead>
                      <TableHead className="text-right">Quantity</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {importData.slice(0, 100).map((item, i) => (
                      <TableRow key={i}>
                        <TableCell className="font-mono text-xs">{item.sku}</TableCell>
                        <TableCell className="text-right">{item.qty}</TableCell>
                      </TableRow>
                    ))}
                    {importData.length > 100 && (
                      <TableRow>
                        <TableCell colSpan={2} className="text-center text-muted-foreground text-xs py-2">
                          ... and {importData.length - 100} more items
                        </TableCell>
                      </TableRow>
                    )}
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
            disabled={importData.length === 0 || loading}
          >
            {loading && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
            {loading ? 'Importing...' : 'Import Products'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
