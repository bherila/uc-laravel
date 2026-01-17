import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { formatCurrency } from '@/lib/currency';

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
  inventoryQuantity?: number;
  priceRange?: {
    maxVariantPrice?: { amount: string };
    minVariantPrice?: { amount: string };
  };
  inventoryItem?: {
    measurement?: {
      weight?: { value: number };
    };
    unitCost?: { amount: string };
  };
  status?: string;
  tags?: string[];
}

interface ShopifyProduct {
  variantId: string;
  variantInventoryQuantity?: number;
}

interface OfferDetail {
  offer_id: number;
  offer_name: string;
  offer_variant_id: string;
  offer_product_name: string;
  offerProductData: ProductData | null;
  manifestGroups: Record<string, ManifestGroup>;
  manifestProductData: Record<string, ProductData>;
  hasOrders: boolean;
  orderCount: number;
  unassignedCount: number;
  inventoryQty: number;
  deficit: number;
}

function VariantLink({ variantId, type }: { variantId: string; type: 'deal' | 'manifest-item' }) {
  const numericId = variantId.replace('gid://shopify/ProductVariant/', '');
  return (
    <a
      href={`https://admin.shopify.com/store/underground-cellar/products/variants/${numericId}`}
      target="_blank"
      rel="noopener noreferrer"
      className="text-xs text-muted-foreground hover:underline font-mono"
    >
      {numericId}
    </a>
  );
}

function OfferDetailPage() {
  const [offer, setOffer] = useState<OfferDetail | null>(null);
  const [shopifyProducts, setShopifyProducts] = useState<ShopifyProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [settingQty, setSettingQty] = useState(false);
  const [deleting, setDeleting] = useState<string | null>(null);

  const root = document.getElementById('offer-detail-root');
  const offerId = root?.dataset.offerId;
  const shopId = root?.dataset.shopId;
  const apiBase = root?.dataset.apiBase || '/api';

  const fetchOffer = useCallback(async () => {
    if (!offerId) return;
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}?detail=1`);
      setOffer(data);
    } catch (err) {
      setError('Failed to load offer');
      console.error(err);
    }
  }, [apiBase, shopId, offerId]);

  const fetchShopifyProducts = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops/${shopId}/shopify/products?type=manifest-item`);
      setShopifyProducts(data);
    } catch (err) {
      console.error('Failed to load Shopify data:', err);
    }
  }, [apiBase, shopId]);

  useEffect(() => {
    Promise.all([fetchOffer(), fetchShopifyProducts()]).finally(() => setLoading(false));
  }, [fetchOffer, fetchShopifyProducts]);

  const setShopifyQuantity = async () => {
    if (!offer?.offerProductData?.variantId) return;
    setSettingQty(true);
    try {
      await fetchWrapper.post(`${apiBase}/shops/${shopId}/shopify/set-inventory`, {
        variant_id: offer.offerProductData.variantId,
        quantity: offer.unassignedCount,
      });
      await fetchOffer();
    } catch (err: any) {
      alert(err?.error || 'Failed to set quantity');
    } finally {
      setSettingQty(false);
    }
  };

  const deleteManifest = async (variantId: string) => {
    if (!confirm('Are you sure you want to remove this product from the offer?')) return;
    setDeleting(variantId);
    try {
      await fetchWrapper.put(`${apiBase}/shops/${shopId}/offers/${offerId}/manifests`, {
        manifests: [{ sku: variantId, qty: 0 }],
      });
      await fetchOffer();
    } catch (err: any) {
      alert(err?.error || 'Failed to delete manifest');
    } finally {
      setDeleting(null);
    }
  };

  if (loading) {
    return (
      <Container>
        <div className="space-y-6">
          <Skeleton className="h-12 w-2/3 mt-5 mb-3" />
          <div className="space-y-2">
            <Skeleton className="h-4 w-1/2" />
          </div>
          <div className="flex gap-2">
            <Skeleton className="h-9 w-40" />
            <Skeleton className="h-9 w-40" />
            <Skeleton className="h-9 w-40" />
          </div>
          <div className="rounded-md border p-4 space-y-4">
            <Skeleton className="h-8 w-full" />
            <Skeleton className="h-20 w-full" />
            <Skeleton className="h-20 w-full" />
          </div>
        </div>
      </Container>
    );
  }

  if (error || !offer) {
    return (
      <Container>
        <MainTitle>Offer Details</MainTitle>
        <div className="text-center py-8 text-destructive">{error || 'Offer not found'}</div>
      </Container>
    );
  }

  const hasManifestProducts = Object.keys(offer.manifestGroups).length > 0;
  const weight = offer.offerProductData?.inventoryItem?.measurement?.weight?.value ?? 0;

  // Calculate total manifest count for percent chance
  const totalManifests = Object.values(offer.manifestGroups).reduce((sum, g) => sum + g.total, 0);

  return (
    <Container>
      <MainTitle>
        [{offer.offer_id}] {offer.offer_name}
      </MainTitle>

      <p className="mb-4 text-sm text-muted-foreground">
        Shopify product: <VariantLink variantId={offer.offer_variant_id} type="deal" />{' '}
        ({offer.offerProductData?.title}), {offer.inventoryQty} inventory
        {weight !== 2 && weight !== 0 && (
          <Badge variant="destructive" className="ml-2">
            Weight should be 2 lbs
          </Badge>
        )}
      </p>

      <div className="flex flex-wrap gap-2 mb-6">
        <Button asChild>
          <a href={`/shop/${shopId}/offers/${offerId}/add-manifest`}>Add Bottles to Offer</a>
        </Button>
        <Button
          variant="secondary"
          asChild
          disabled={!offer.hasOrders}
          className={!offer.hasOrders ? 'opacity-50 pointer-events-none' : ''}
        >
          <a href={`/shop/${shopId}/offers/${offerId}/shopify_manifests`}>View order manifests</a>
        </Button>
        <Button
          variant="secondary"
          asChild
          disabled={!hasManifestProducts}
          className={!hasManifestProducts ? 'opacity-50 pointer-events-none' : ''}
        >
          <a href={`/shop/${shopId}/offers/${offerId}/profitability`}>View Profitability</a>
        </Button>
        <Button
          variant="secondary"
          asChild
          disabled={!hasManifestProducts}
          className={!hasManifestProducts ? 'opacity-50 pointer-events-none' : ''}
        >
          <a href={`/shop/${shopId}/offers/${offerId}/metafields`}>View Metafields</a>
        </Button>
      </div>

      {!offer.hasOrders && (
        <Alert className="mb-6">
          <AlertTitle>No orders yet. Reminder!</AlertTitle>
          <AlertDescription>
            Ensure that nobody is able to purchase this product online until you are done setting up
            the bottles. The total number of bottles that can be allocated to the offer is based on
            the quantity available of the product id listed above.
          </AlertDescription>
        </Alert>
      )}

      {offer.deficit > 0 && (
        <Alert variant="destructive" className="mb-6">
          <AlertTitle>Inventory Mismatch!</AlertTitle>
          <AlertDescription className="space-y-2">
            <p>
              There are QTY={offer.inventoryQty} available of the OFFER SKU in Shopify, however
              there are {offer.unassignedCount} unassigned bottles in this deal. This will result
              in the deal not allocating correctly.
            </p>
            <p>
              <strong>To fix it:</strong> Set the quantity available in Shopify to{' '}
              {offer.unassignedCount}, and then refresh this page.
            </p>
            <Button
              variant="outline"
              onClick={setShopifyQuantity}
              disabled={settingQty}
            >
              {settingQty ? 'Setting...' : `Set Shopify Quantity to ${offer.unassignedCount}`}
            </Button>
          </AlertDescription>
        </Alert>
      )}

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Product</TableHead>
              <TableHead>Value</TableHead>
              <TableHead># Offered</TableHead>
              {offer.hasOrders && <TableHead># Allocated</TableHead>}
              {offer.hasOrders && <TableHead># Remaining</TableHead>}
              <TableHead>% Chance</TableHead>
              <TableHead>Shopify Inventory</TableHead>
              {!offer.hasOrders && <TableHead>Action</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {Object.keys(offer.manifestGroups).length === 0 ? (
              <TableRow>
                <TableCell colSpan={offer.hasOrders ? 7 : 7} className="text-center py-8 text-muted-foreground">
                  No products added yet. Add bottles to get started.
                </TableCell>
              </TableRow>
            ) : (
              Object.entries(offer.manifestGroups).map(([variantId, group]) => {
                const product = offer.manifestProductData[variantId];
                const shopifyProduct = shopifyProducts.find((p) => p.variantId === variantId);
                const percentChance = totalManifests > 0 ? (group.total / totalManifests) * 100 : 0;
                const maxPrice = product?.priceRange?.maxVariantPrice?.amount;
                const weight = product?.inventoryItem?.measurement?.weight?.value ?? 0;

                return (
                  <TableRow key={variantId}>
                    <TableCell>
                      <div className="space-y-1">
                        <div className="font-medium">{product?.title ?? '??'}</div>
                        <VariantLink variantId={variantId} type="manifest-item" />
                        {weight > 1 && (
                          <Badge variant="destructive" className="ml-2">
                            Weight should be zero
                          </Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      {maxPrice ? formatCurrency(parseFloat(maxPrice)) : 'null'}
                    </TableCell>
                    <TableCell>{group.total}</TableCell>
                    {offer.hasOrders && <TableCell>{group.allocated}</TableCell>}
                    {offer.hasOrders && <TableCell>{group.total - group.allocated}</TableCell>}
                    <TableCell>{percentChance.toFixed(2)}%</TableCell>
                    <TableCell>{shopifyProduct?.variantInventoryQuantity ?? '-'}</TableCell>
                    {!offer.hasOrders && (
                      <TableCell>
                        {group.allocated === 0 && (
                          <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => deleteManifest(variantId)}
                            disabled={deleting === variantId}
                          >
                            {deleting === variantId ? 'Deleting...' : 'Delete'}
                          </Button>
                        )}
                      </TableCell>
                    )}
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>

      <div className="mt-6">
        <Button variant="outline" asChild>
          <a href={`/shop/${shopId}/offers`}>← Back to Offers</a>
        </Button>
      </div>
    </Container>
  );
}

const element = document.getElementById('offer-detail-root');
if (element) {
  createRoot(element).render(<OfferDetailPage />);
}
