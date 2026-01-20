import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import VariantLink from '@/components/VariantLink';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import RenderRelativeTimeInterval from '@/components/RenderRelativeTimeInterval';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Archive, ArchiveRestore, Trash2 } from 'lucide-react';

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
  is_archived: boolean;
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

// RenderRelativeTimeInterval component handles date formatting and live updates

function OfferListPage() {
  const [offers, setOffers] = useState<Offer[]>([]);
  const [shopifyData, setShopifyData] = useState<ShopifyProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<'active' | 'archived'>('active');
  
  const rootEl = document.getElementById('offers-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const shopId = rootEl?.dataset.shopId;

  const fetchOffers = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers?status=${status}`);
      setOffers(data);
    } catch (err) {
      setError('Failed to load offers');
      console.error(err);
    }
  }, [apiBase, shopId, status]);

  const fetchShopifyData = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops/${shopId}/shopify/products?type=deal`);
      setShopifyData(data);
    } catch (err) {
      console.error('Failed to load Shopify data:', err);
    }
  }, [apiBase, shopId]);

  useEffect(() => {
    setLoading(true);
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

  const archiveOffer = async (id: number) => {
    try {
      await fetchWrapper.post(`${apiBase}/shops/${shopId}/offers/${id}/archive`, {});
      fetchOffers();
    } catch (err: any) {
      alert(err?.error || 'Failed to archive offer');
    }
  };

  const unarchiveOffer = async (id: number) => {
    try {
      await fetchWrapper.post(`${apiBase}/shops/${shopId}/offers/${id}/unarchive`, {});
      fetchOffers();
    } catch (err: any) {
      alert(err?.error || 'Failed to unarchive offer');
    }
  };

  const getProductName = (offer: Offer): string => {
    const shopifyProduct = shopifyData.find(d => d.variantId === offer.offerProductData?.variantId);
    return shopifyProduct?.productName || offer.offerProductData?.title || '-';
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

      <div className="mb-4 flex justify-between items-center">
        <Button asChild>
          <a href={`/shop/${shopId}/offers/new`}>Create New Offer</a>
        </Button>

        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground">View:</span>
          <Select value={status} onValueChange={(val: any) => setStatus(val)}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="active">Active Offers</SelectItem>
              <SelectItem value="archived">Archived Offers</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-[60px]">ID</TableHead>
              <TableHead>Offer Name</TableHead>
              <TableHead>Deal SKU</TableHead>
              <TableHead>Date from Metafield</TableHead>
              <TableHead className="text-right w-[120px]">Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {offers.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                  {status === 'active' ? 'No offers found. Create one to get started.' : 'No archived offers found.'}
                </TableCell>
              </TableRow>
            ) : (
              offers.map((offer) => {
                const hasEnded = offer.offerProductData?.endDate 
                    ? new Date(offer.offerProductData.endDate) < new Date()
                    : false;

                return (
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
                          <VariantLink
                            variantId={offer.offerProductData.variantId}
                            productId={offer.offerProductData.productId}
                            shopDomain={offer.shop?.shop_domain}
                          />
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
                      <RenderRelativeTimeInterval
                        startDate={offer.offerProductData?.startDate}
                        endDate={offer.offerProductData?.endDate}
                      />
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        {offer.is_archived ? (
                          <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={() => unarchiveOffer(offer.offer_id)}
                          >
                            <ArchiveRestore className="w-4 h-4 mr-1" />
                            Unarchive
                          </Button>
                        ) : hasEnded ? (
                          <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={() => archiveOffer(offer.offer_id)}
                          >
                            <Archive className="w-4 h-4 mr-1" />
                            Archive
                          </Button>
                        ) : (
                          <Button 
                            variant="destructive" 
                            size="sm" 
                            onClick={() => deleteOffer(offer.offer_id)}
                          >
                            <Trash2 className="w-4 h-4 mr-1" />
                            Delete
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })
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