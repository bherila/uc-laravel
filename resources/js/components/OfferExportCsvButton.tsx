import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { FileDown, Clipboard, Check, Download } from 'lucide-react';
import { toast } from 'sonner';

interface ManifestGroup {
  total: number;
  allocated: number;
  manifests: Array<{
    manifest_id: number;
    assignee_id: string | null;
    assignment_ordering: number;
  }>;
}

interface ProductData {
  variantId?: string;
  productId?: string;
  title?: string;
  sku?: string;
  inventoryQuantity?: number;
  priceRange?: {
    maxVariantPrice?: { amount: string };
    minVariantPrice?: { amount: string };
  };
}

interface ShopifyProduct {
  variantId: string;
  variantInventoryQuantity?: number;
}

interface OfferExportCsvButtonProps {
  shopId: string | number;
  offerId: string | number;
  manifestGroups: Record<string, ManifestGroup>;
  manifestProductData: Record<string, ProductData>;
  shopifyProducts: ShopifyProduct[];
}

export function OfferExportCsvButton({
  shopId,
  offerId,
  manifestGroups,
  manifestProductData,
  shopifyProducts,
}: OfferExportCsvButtonProps) {
  const [open, setOpen] = useState(false);
  const [copied, setCopied] = useState(false);

  const generateCsv = () => {
    const headers = [
      'Product Name',
      'Variant ID',
      'SKU',
      'Value',
      'Num Offered',
      'Num Allocated',
      'Num Remaining',
      'Chance',
      'Shopify Inventory',
    ];

    const totalManifests = Object.values(manifestGroups).reduce((sum, g) => sum + g.total, 0);

    const rows = Object.entries(manifestGroups).map(([variantId, group]) => {
      const product = manifestProductData[variantId];
      const shopifyProduct = shopifyProducts.find((p) => p.variantId === variantId);
      const percentChance = totalManifests > 0 ? (group.total / totalManifests) * 100 : 0;
      const maxPrice = product?.priceRange?.maxVariantPrice?.amount ?? '0';

      return [
        `"${(product?.title ?? '??').replace(/"/g, '""')}"`,
        variantId,
        product?.sku ?? '',
        maxPrice,
        group.total,
        group.allocated,
        group.total - group.allocated,
        `${percentChance.toFixed(2)}%`,
        shopifyProduct?.variantInventoryQuantity ?? 0,
      ];
    });

    return [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
  };

  const csvContent = generateCsv();

  const handleCopy = () => {
    navigator.clipboard.writeText(csvContent);
    setCopied(true);
    toast.success('CSV copied to clipboard');
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownload = () => {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `shop${shopId}_offer${offerId}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    toast.success('CSV file saved');
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <FileDown className="w-4 h-4 mr-2" />
          Export CSV
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Export Offer Data (CSV)</DialogTitle>
        </DialogHeader>
        <div className="py-4">
          <Textarea
            readOnly
            value={csvContent}
            className="font-mono text-xs h-64 resize-none"
          />
        </div>
        <DialogFooter className="flex justify-between sm:justify-between items-center">
          <div className="text-xs text-muted-foreground">
            {Object.keys(manifestGroups).length} products included
          </div>
          <div className="flex gap-2">
            <Button variant="secondary" onClick={handleCopy} disabled={!csvContent}>
              {copied ? <Check className="w-4 h-4 mr-2" /> : <Clipboard className="w-4 h-4 mr-2" />}
              {copied ? 'Copied' : 'Copy CSV'}
            </Button>
            <Button onClick={handleDownload} disabled={!csvContent}>
              <Download className="w-4 h-4 mr-2" />
              Save CSV
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
