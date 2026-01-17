import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useMemo } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fetchWrapper } from '@/fetchWrapper';

interface ShopifyProduct {
  variantId: string;
  productId: string;
  productName: string;
  variantName: string;
  variantSku: string;
  tags: string[];
}

function ProductSelector({
  options,
  disabledOptions,
  selectedValue,
  onSelect,
}: {
  options: ShopifyProduct[];
  disabledOptions: string[];
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

  // Sort: enabled first, then disabled
  const sortedOptions = useMemo(() => {
    return [
      ...filteredOptions.filter((option) => !disabledOptions.includes(option.variantId)),
      ...filteredOptions.filter((option) => disabledOptions.includes(option.variantId)),
    ];
  }, [filteredOptions, disabledOptions]);

  useEffect(() => {
    if (sortedOptions.length === 1 && !disabledOptions.includes(sortedOptions[0].variantId)) {
      onSelect(sortedOptions[0]);
    }
  }, [sortedOptions, disabledOptions, onSelect]);

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
        {sortedOptions.length !== 1 && (
          <option value="">({sortedOptions.length} options)</option>
        )}
        {sortedOptions.map((option) => (
          <option
            key={option.productId + '|' + option.variantId}
            value={JSON.stringify(option)}
            disabled={disabledOptions.includes(option.variantId)}
          >
            {option.productName} - {option.variantName}
            {disabledOptions.includes(option.variantId) ? ' (Already in use)' : ''}
            {sortedOptions.length === 1 && !disabledOptions.includes(option.variantId) ? ' ✅' : ''}
          </option>
        ))}
      </select>
    </div>
  );
}

function NewOfferPage() {
  const [products, setProducts] = useState<ShopifyProduct[]>([]);
  const [existingVariantIds, setExistingVariantIds] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [offerName, setOfferName] = useState('');
  const [selectedProduct, setSelectedProduct] = useState<ShopifyProduct | null>(null);

  const apiBase = document.getElementById('offer-new-root')?.dataset.apiBase || '/api';

  useEffect(() => {
    const loadData = async () => {
      try {
        // Load available deal products and existing offers in parallel
        const [productsData, offersData] = await Promise.all([
          fetchWrapper.get(`${apiBase}/shopify/products?type=deal`),
          fetchWrapper.get(`${apiBase}/offers`),
        ]);
        setProducts(productsData);
        setExistingVariantIds(
          offersData
            .map((offer: any) => offer.offerProductData?.variantId)
            .filter(Boolean)
        );
      } catch (err) {
        console.error('Failed to load data:', err);
        setError('Failed to load product data');
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [apiBase]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProduct || !offerName.trim()) return;

    setSubmitting(true);
    try {
      await fetchWrapper.post(`${apiBase}/offers`, {
        offer_name: offerName.trim(),
        offer_variant_id: selectedProduct.variantId,
        offer_product_name: selectedProduct.productName,
      });
      // Redirect to offers list on success
      window.location.href = '/offers';
    } catch (err: any) {
      setError(err?.error || 'Failed to create offer');
      setSubmitting(false);
    }
  };

  const isValid = offerName.trim().length > 0 && selectedProduct !== null;

  if (loading) {
    return (
      <Container>
        <MainTitle>Create New Offer</MainTitle>
        <div className="text-center py-8 text-muted-foreground">Loading...</div>
      </Container>
    );
  }

  return (
    <Container>
      <MainTitle>Create New Offer</MainTitle>

      {error && (
        <div className="bg-destructive/15 text-destructive px-4 py-3 rounded-md mb-4">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
        <div className="space-y-2">
          <Label htmlFor="offerName">
            Offer Name
            {!offerName.trim() && <span className="text-destructive ml-1">*</span>}
          </Label>
          <Input
            id="offerName"
            type="text"
            value={offerName}
            onChange={(e) => setOfferName(e.target.value)}
            placeholder="e.g. January 2024 Wine Club"
            required
          />
        </div>

        <div className="space-y-2">
          <Label>
            Deal Product/Variant
            {!selectedProduct && <span className="text-destructive ml-1">*</span>}
          </Label>
          <ProductSelector
            options={products}
            disabledOptions={existingVariantIds}
            selectedValue={selectedProduct}
            onSelect={setSelectedProduct}
          />
          {selectedProduct && (
            <p className="text-sm text-muted-foreground">
              Selected: {selectedProduct.productName} - {selectedProduct.variantName}
            </p>
          )}
        </div>

        <div className="flex gap-4">
          <Button type="submit" disabled={!isValid || submitting}>
            {submitting ? 'Creating...' : 'Create Offer'}
          </Button>
          <Button type="button" variant="outline" onClick={() => (window.location.href = '/offers')}>
            Cancel
          </Button>
        </div>
      </form>
    </Container>
  );
}

const element = document.getElementById('offer-new-root');
if (element) {
  createRoot(element).render(<NewOfferPage />);
}
