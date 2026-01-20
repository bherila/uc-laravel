import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useMemo } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';

interface ShopifyProduct {
  variantId: string;
  productId: string;
  productName: string;
  variantName: string;
  variantSku: string;
  variantInventoryQuantity: number;
  tags: string[];
}

function ProductSelector({
  options,
  selectedValue,
  onSelect,
}: {
  options: ShopifyProduct[];
  selectedValue: ShopifyProduct | null;
  onSelect: (product: ShopifyProduct | null) => void;
}) {
  const [searchText, setSearchText] = useState('');

  const filteredOptions = useMemo(() => {
    if (!searchText) return options;
    const searchWords = searchText.toLowerCase().split(' ').filter(Boolean);
    return options.filter((option) => {
      const lcjson = JSON.stringify(option).toLowerCase();
      return searchWords.every((word) => lcjson.includes(word));
    });
  }, [searchText, options]);

  useEffect(() => {
    if (filteredOptions.length === 1) {
      onSelect(filteredOptions[0] || null);
    }
  }, [filteredOptions, onSelect]);

  return (
    <div className="space-y-2">
      <Input
        placeholder="Search products..."
        value={searchText}
        onChange={(e) => setSearchText(e.target.value)}
      />
      <select
        size={8}
        className="w-full border rounded-md p-2 bg-background text-foreground"
        value={JSON.stringify(selectedValue)}
        onChange={(e) => {
          const val = e.target.value;
          onSelect(val ? JSON.parse(val) : null);
        }}
      >
        {filteredOptions.length !== 1 && (
          <option value="">({filteredOptions.length} options)</option>
        )}
        {filteredOptions.map((option) => (
          <option
            key={option.productId + '|' + option.variantId}
            value={JSON.stringify(option)}
          >
            {option.productName} - {option.variantName}
            {' '}(SKU: {option.variantSku}, Inv: {option.variantInventoryQuantity})
            {filteredOptions.length === 1 ? ' ✅' : ''}
          </option>
        ))}
      </select>
    </div>
  );
}

function AddManifestPage() {
  const [products, setProducts] = useState<ShopifyProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [offer, setOffer] = useState<any>(null);

  const [selectedProduct, setSelectedProduct] = useState<ShopifyProduct | null>(null);
  const [qty, setQty] = useState('1');

  const root = document.getElementById('offer-add-manifest-root');
  const offerId = root?.dataset.offerId;
  const shopId = root?.dataset.shopId;
  const apiBase = root?.dataset.apiBase || '/api';
  const canWrite = root?.dataset.canWriteShop === 'true';

  useEffect(() => {
    if (!loading && !canWrite) {
      window.location.href = `/shop/${shopId}/offers/${offerId}`;
    }
  }, [canWrite, loading, shopId, offerId]);

  useEffect(() => {
    const loadData = async () => {
      try {
        const [productsData, offerData] = await Promise.all([
          fetchWrapper.get(`${apiBase}/shops/${shopId}/shopify/products?type=manifest-item`),
          fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}`),
        ]);
        setProducts(productsData);
        setOffer(offerData);
      } catch (err) {
        console.error('Failed to load data:', err);
        setError('Failed to load product data');
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [apiBase, shopId, offerId]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProduct || !qty) return;

    const qtyNum = parseInt(qty, 10);
    if (isNaN(qtyNum) || qtyNum <= 0) {
      setError('Quantity must be a positive number');
      return;
    }

    setSubmitting(true);
    setError(null);
    try {
      await fetchWrapper.put(`${apiBase}/shops/${shopId}/offers/${offerId}/manifests`, {
        manifests: [{ sku: selectedProduct.variantId, qty: qtyNum }],
      });
      // Redirect to offer detail on success
      window.location.href = `/shop/${shopId}/offers/${offerId}`;
    } catch (err: any) {
      setError(err?.error || 'Failed to add manifest');
      setSubmitting(false);
    }
  };

  const isValidQty = !isNaN(parseInt(qty, 10)) && parseInt(qty, 10) > 0;
  const isValid = selectedProduct !== null && isValidQty;

  if (loading) {
    return (
      <Container>
        <div className="space-y-6">
          <Skeleton className="h-12 w-2/3 mt-5 mb-3" />
          <div className="text-center py-8 text-muted-foreground">Loading...</div>
        </div>
      </Container>
    );
  }

  if (error || !offer) {
    return (
      <Container>
        <MainTitle>Add Bottles to Offer</MainTitle>
        <div className="text-center py-8 text-destructive">{error || 'Offer not found'}</div>
      </Container>
    );
  }

  return (
    <Container>
      <ShopOfferBreadcrumb 
        shopId={shopId!} 
        shopName={offer.shop?.name} 
        offer={{ id: offer.offer_id, name: offer.offer_name }}
        action="Add Bottles"
      />

      {error && (
        <div className="bg-destructive/15 text-destructive px-4 py-3 rounded-md mb-4">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
        <div className="space-y-2">
          <Label>
            Product/Variant
            {!selectedProduct && <span className="text-destructive ml-1">*</span>}
          </Label>
          <ProductSelector
            options={products}
            selectedValue={selectedProduct}
            onSelect={setSelectedProduct}
          />
          {selectedProduct && (
            <p className="text-sm text-muted-foreground">
              Selected: {selectedProduct.productName} - {selectedProduct.variantName}
            </p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="qty">
            Quantity in offer
            {!isValidQty && <span className="text-destructive ml-1">*</span>}
          </Label>
          <Input
            id="qty"
            type="number"
            min="1"
            value={qty}
            onChange={(e) => setQty(e.target.value)}
            className="max-w-[150px]"
            required
          />
        </div>

        <div className="flex gap-4">
          <Button type="submit" disabled={!isValid || submitting}>
            {submitting ? 'Adding...' : 'Add to Offer'}
          </Button>
          <Button type="button" variant="outline" onClick={() => (window.location.href = `/shop/${shopId}/offers/${offerId}`)}>
            Cancel
          </Button>
        </div>
      </form>
    </Container>
  );
}

const element = document.getElementById('offer-add-manifest-root');
if (element) {
  createRoot(element).render(<AddManifestPage />);
}
