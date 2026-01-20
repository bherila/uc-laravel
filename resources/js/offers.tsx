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
import { Archive, ArchiveRestore, Trash2, Loader2 } from 'lucide-react';
import { SimplePagination } from '@/components/SimplePagination';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { CleanupOffersButton } from '@/components/CleanupOffersButton';
import { OfferRemoval } from '@/components/OfferRemoval';

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
  total_manifests_count: number;
  allocated_manifests_count: number;
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

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}

function OfferListPage() {
  const [data, setData] = useState<PaginatedResponse<Offer> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<'active' | 'archived'>('active');
  const [page, setPage] = useState(1);
  const [actionLoading, setActionLoading] = useState<number | null>(null);
  
  const rootEl = document.getElementById('offers-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const shopId = rootEl?.dataset.shopId;
  const canWrite = rootEl?.dataset.canWriteShop === 'true';

  // Initialize from URL
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const pageParam = params.get('page');
    const statusParam = params.get('status');
    
    if (pageParam) setPage(parseInt(pageParam, 10));
    if (statusParam === 'active' || statusParam === 'archived') setStatus(statusParam);
  }, []);

  const fetchOffers = useCallback(async (pageNum: number, currentStatus: string) => {
    setLoading(true);
    try {
      // Update URL
      const params = new URLSearchParams();
      if (pageNum > 1) params.set('page', pageNum.toString());
      if (currentStatus !== 'active') params.set('status', currentStatus);
      
      const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
      window.history.replaceState({}, '', newUrl);

      const result = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers?status=${currentStatus}&page=${pageNum}`);
      setData(result);

      // If this page is empty and we're not on page 1, go back a page
      if (result.data.length === 0 && pageNum > 1) {
        setPage(pageNum - 1);
      }
    } catch (err) {
      setError('Failed to load offers');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase, shopId]);

  useEffect(() => {
    fetchOffers(page, status);
  }, [page, status, fetchOffers]);

  const deleteOffer = async (id: number) => {
    if (!confirm('Are you sure you want to delete this offer? This will also delete any unassigned manifests.')) {
      return;
    }
    
    setActionLoading(id);
    try {
      await fetchWrapper.delete(`${apiBase}/shops/${shopId}/offers/${id}`, {});
      fetchOffers(page, status);
    } catch (err: any) {
      alert(err?.error || 'Failed to delete offer');
    } finally {
      setActionLoading(null);
    }
  };

  const archiveOffer = async (id: number) => {
    setActionLoading(id);
    try {
      await fetchWrapper.post(`${apiBase}/shops/${shopId}/offers/${id}/archive`, {});
      fetchOffers(page, status);
    } catch (err: any) {
      alert(err?.error || 'Failed to archive offer');
    } finally {
      setActionLoading(null);
    }
  };

  const unarchiveOffer = async (id: number) => {
    setActionLoading(id);
    try {
      await fetchWrapper.post(`${apiBase}/shops/${shopId}/offers/${id}/unarchive`, {});
      fetchOffers(page, status);
    } catch (err: any) {
      alert(err?.error || 'Failed to unarchive offer');
    } finally {
      setActionLoading(null);
    }
  };

  const handleStatusChange = (val: 'active' | 'archived') => {
    setStatus(val);
    setPage(1);
  };

  const handlePageChange = (newPage: number) => {
    setPage(newPage);
  };

  const getProductName = (offer: Offer): string => {
    return offer.offerProductData?.title || '-';
  };

  const shopName = data?.data[0]?.shop?.name || 'Loading...';

  if (loading && !data) {
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
    <TooltipProvider>
      <Container>
        <ShopOfferBreadcrumb 
          shopId={shopId!} 
          shopName={shopName} 
        />

      <div className="mb-4 flex justify-between items-center">
        <div className="flex items-center">
          {canWrite && (
            <Button asChild>
              <a href={`/shop/${shopId}/offers/new`}>Create New Offer</a>
            </Button>
          )}
          
          {canWrite && status === 'active' && (
            <CleanupOffersButton 
              shopId={shopId!} 
              apiBase={apiBase} 
              onCleanupSuccess={() => fetchOffers(page, status)} 
            />
          )}
        </div>

        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2">
            <span className="text-sm text-muted-foreground">View:</span>
            <Select value={status} onValueChange={(val: any) => handleStatusChange(val)}>
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active">Active Offers</SelectItem>
                <SelectItem value="archived">Archived Offers</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {data && (
            <SimplePagination 
                currentPage={data.current_page} 
                lastPage={data.last_page} 
                onPageChange={handlePageChange}
                loading={loading}
            />
          )}
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
              <TableHead className="text-center"># Offered</TableHead>
              <TableHead className="text-center"># Allocated</TableHead>
              {canWrite && <TableHead className="text-right w-[120px]">Action</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading && !data ? (
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                    <TableCell><Skeleton className="h-4 w-8" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-40" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-40" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-40" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-12" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-12" /></TableCell>
                    {canWrite && <TableCell><Skeleton className="h-4 w-20" /></TableCell>}
                </TableRow>
              ))
            ) : !data || data.data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={canWrite ? 7 : 6} className="text-center py-8 text-muted-foreground">
                  {status === 'active' ? 'No offers found. Create one to get started.' : 'No archived offers found.'}
                </TableCell>
              </TableRow>
            ) : (
              data.data.map((offer) => {
                const hasEnded = offer.offerProductData?.endDate 
                    ? new Date(offer.offerProductData.endDate) < new Date()
                    : false;
                
                const isItemLoading = actionLoading === offer.offer_id;

                return (
                  <TableRow key={offer.offer_id} className={loading ? 'opacity-50' : ''}>
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
                    <TableCell className="text-center font-mono text-sm">
                      {offer.total_manifests_count}
                    </TableCell>
                    <TableCell className="text-center font-mono text-sm">
                      {offer.allocated_manifests_count}
                    </TableCell>
                    {canWrite && (
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          {offer.is_archived ? (
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button 
                                  variant="outline" 
                                  size="sm" 
                                  onClick={() => unarchiveOffer(offer.offer_id)}
                                  disabled={isItemLoading}
                                >
                                  {isItemLoading ? (
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                  ) : (
                                    <ArchiveRestore className="w-4 h-4" />
                                  )}
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>Unarchive Offer</TooltipContent>
                            </Tooltip>
                          ) : hasEnded ? (
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button 
                                  variant="outline" 
                                  size="sm" 
                                  onClick={() => archiveOffer(offer.offer_id)}
                                  disabled={isItemLoading}
                                >
                                  {isItemLoading ? (
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                  ) : (
                                    <Archive className="w-4 h-4" />
                                  )}
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>Archive Offer</TooltipContent>
                            </Tooltip>
                          ) : (
                            <OfferRemoval
                              offerId={offer.offer_id}
                              allocatedCount={offer.allocated_manifests_count}
                              isDeleting={isItemLoading}
                              onDelete={deleteOffer}
                            />
                          )}
                        </div>
                      </TableCell>
                    )}
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>

      <div className="flex justify-end mt-4">
        {data && (
            <SimplePagination 
                currentPage={data.current_page} 
                lastPage={data.last_page} 
                onPageChange={handlePageChange}
                loading={loading}
            />
        )}
      </div>
    </Container>
    </TooltipProvider>
  );
}

const offersElement = document.getElementById('offers-root');
if (offersElement) {
  createRoot(offersElement).render(<OfferListPage />);
}