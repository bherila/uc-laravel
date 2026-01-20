import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { Store, ArrowRight, ExternalLink } from 'lucide-react';

interface OfferProductData {
  variantId: string;
  productId?: string;
  title?: string;
  inventoryQuantity?: number;
  startDate?: string;
  endDate?: string;
  status?: string;
  tags?: string[];
}

interface Offer {
  offer_id: number;
  offer_name: string;
  shop_id: number;
  shop?: {
    id: number;
    name: string;
    shop_domain: string;
  };
  offerProductData?: OfferProductData;
}

interface ShopifyProduct {
  variantId: string;
  productId: string;
  productName: string;
  variantName: string;
  variantSku: string;
  tags: string[];
}

function formatDateRange(startDate?: string, endDate?: string): string {
  if (!startDate && !endDate) return '-';
  
  const now = new Date();
  const parts: string[] = [];
  
  if (startDate) {
    const start = parseISO(startDate);
    if (isAfter(start, now)) {
      parts.push(`Starts ${formatDistanceToNow(start, { addSuffix: true })}`);
    } else {
      parts.push(`Started ${formatDistanceToNow(start, { addSuffix: true })}`);
    }
  }
  
  if (endDate) {
    const end = parseISO(endDate);
    if (isAfter(end, now)) {
      parts.push(`Ends ${formatDistanceToNow(end, { addSuffix: true })}`);
    } else {
      parts.push(`Ended ${formatDistanceToNow(end, { addSuffix: true })}`);
    }
  }
  
  return parts.join(' • ');
}

function OfferListPage() {
  const [offers, setOffers] = useState<Offer[]>([]);
  const [shopifyData, setShopifyData] = useState<ShopifyProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const rootEl = document.getElementById('offers-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const shopId = rootEl?.dataset.shopId;

  const fetchOffers = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers`);
      setOffers(data);
    } catch (err) {
      setError('Failed to load offers');
      console.error(err);
    }
  }, [apiBase, shopId]);

  const fetchShopifyData = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops/${shopId}/shopify/products?type=deal`);
      setShopifyData(data);
    } catch (err) {
      console.error('Failed to load Shopify data:', err);
    }
  }, [apiBase, shopId]);

  useEffect(() => {
    Promise.all([fetchOffers(), fetchShopifyData()]).finally(() => setLoading(false));
  }, [fetchOffers, fetchShopifyData]);

  const deleteOffer = async (id: number) => {
    if (!confirm('Are you sure you want to delete this offer? This will also delete any unassigned manifests.')) {
      return;
    }
    
    try {
      await fetchWrapper.delete(`${apiBase}/shops/${shopId}/offers/${id}`, {});
      fetchOffers();
    } catch (err: any) {
      alert(err?.error || 'Failed to delete offer');
    }
  };

  const getProductName = (offer: Offer): string => {
    const shopifyProduct = shopifyData.find(d => d.variantId === offer.offerProductData?.variantId);
    return shopifyProduct?.productName || offer.offerProductData?.title || '-';
  };

  const getVariantLink = (offer: Offer): string => {
    const variantId = offer.offerProductData?.variantId;
    if (!variantId) return '#';
    const numericId = variantId.replace('gid://shopify/ProductVariant/', '');
    const shopSlug = offer.shop?.shop_domain?.replace('.myshopify.com', '') || 'underground-cellar';
    return `https://admin.shopify.com/store/${shopSlug}/products/variants/${numericId}`;
  };

  const shopName = offers[0]?.shop?.name || 'Loading...';

  if (loading) {
    return (
      <Container>
        <div className="space-y-6">
          <Skeleton className="h-12 w-1/4 mt-5 mb-3" />
          <Skeleton className="h-10 w-40" />
          <div className="rounded-md border">
            <div className="p-4 border-b">
              <Skeleton className="h-8 w-full" />
            </div>
            <div className="p-4 space-y-4">
              <Skeleton className="h-16 w-full" />
              <Skeleton className="h-16 w-full" />
              <Skeleton className="h-16 w-full" />
              <Skeleton className="h-16 w-full" />
              <Skeleton className="h-16 w-full" />
            </div>
          </div>
        </div>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <MainTitle>Offers</MainTitle>
        <div className="text-center py-8 text-destructive">{error}</div>
      </Container>
    );
  }

  return (
    <Container>
      <ShopOfferBreadcrumb 
        shopId={shopId!} 
        shopName={shopName} 
      />

      <div className="mb-4">
        <Button asChild>
          <a href={`/shop/${shopId}/offers/new`}>Create New Offer</a>
        </Button>
      </div>
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-[60px]">ID</TableHead>
              <TableHead>Offer Name</TableHead>
              <TableHead>Deal SKU</TableHead>
              <TableHead>Date from Metafield</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right w-[100px]">&nbsp;</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {offers.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                  No offers found. Create one to get started.
                </TableCell>
              </TableRow>
            ) : (
              offers.map((offer) => (
                <TableRow key={offer.offer_id}>
                  <TableCell className="font-mono text-sm">{offer.offer_id}</TableCell>
                  <TableCell>
                    <a 
                      href={`/shop/${shopId}/offers/${offer.offer_id}`}
                      className="hover:underline font-medium"
                    >
                      {offer.offer_name}
                    </a>
                  </TableCell>
                  <TableCell>
                    <div className="flex flex-col gap-1">
                      <span className="text-sm">{getProductName(offer)}</span>
                      {offer.offerProductData?.variantId && (
                        <a 
                          href={getVariantLink(offer)}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-xs text-muted-foreground hover:underline font-mono flex items-center gap-1"
                        >
                          {offer.offerProductData.variantId.replace('gid://shopify/ProductVariant/', '')}
                          <ExternalLink className="w-3 h-3" />
                        </a>
                      )}
                      <div className="flex flex-wrap gap-1">
                        {(offer.offerProductData?.tags ?? []).map((tag) => (
                          <Badge key={tag} variant="secondary" className="text-xs">
                            {tag}
                          </Badge>
                        ))}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell className="text-sm">
                    {formatDateRange(offer.offerProductData?.startDate, offer.offerProductData?.endDate)}
                  </TableCell>
                  <TableCell>
                    {offer.offerProductData?.status && (
                      <Badge 
                        variant={offer.offerProductData.status === 'ACTIVE' ? 'default' : 'secondary'}
                      >
                        {offer.offerProductData.status}
                      </Badge>
                    )}
                  </TableCell>
                  <TableCell className="text-right">
                    <Button 
                      variant="destructive" 
                      size="sm" 
                      onClick={() => deleteOffer(offer.offer_id)}
                    >
                      Delete
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </Container>
  );
}

const offersElement = document.getElementById('offers-root');
if (offersElement) {
  createRoot(offersElement).render(<OfferListPage />);
}