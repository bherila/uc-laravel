import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { formatCurrency } from '@/lib/currency';
import { ExternalLink, RefreshCw, Loader2 } from 'lucide-react';
import { formatDistanceToNow, parseISO } from 'date-fns';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";

interface LineItem {
  line_item_id: string;
  currentQuantity: number;
  title: string;
  variant_variant_graphql_id: string | null;
  originalUnitPriceSet_shopMoney_amount: number;
  discountedTotalSet_shopMoney_amount: number;
}

interface Order {
  id: string;
  createdAt: string;
  email: string;
  displayFinancialStatus: string;
  displayFulfillmentStatus: string;
  cancelledAt: string | null;
  totalPrice: number;
  purchasedItems: LineItem[];
  upgradeItems: LineItem[];
  purchasedQty: number;
  upgradeQty: number;
  purchasedValue: number;
  upgradeValue: number;
  upgradeCost: number;
  isQtyEqual: boolean;
  fulfillments_nodes: {
    status: string;
    trackingInfo: {
      number: string;
      url: string | null;
    }[];
  }[];
  fulfillmentOrders_nodes: {
    id: string;
    status: string;
    deliveryMethod: {
      methodType: string;
      presentedName: string;
    } | null;
  }[];
}

interface OfferOrders {
  offer_id: number;
  offer_name: string;
  variant_id: string;
  shop_id: number;
  shop?: {
    id: number;
    name: string;
    shop_domain: string;
  };
  orders: Order[];
  totals: {
    orderCount: number;
    purchasedQty: number;
    upgradeQty: number;
    purchasedValue: number;
    upgradeValue: number;
    upgradeCost: number;
  };
}

function getShopifyOrderUrl(orderId: string, shopDomain?: string): string {
  const numericId = orderId.replace('gid://shopify/Order/', '');
  const shopSlug = shopDomain?.replace('.myshopify.com', '') || 'underground-cellar';
  return `https://admin.shopify.com/store/${shopSlug}/orders/${numericId}`;
}

function ShopifyManifestsPage() {
  const [data, setData] = useState<OfferOrders | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [repickingOrderId, setRepickingOrderId] = useState<string | null>(null);
  const [combiningOrderId, setCombiningOrderId] = useState<string | null>(null);

  const root = document.getElementById('offer-manifests-root');
  const offerId = root?.dataset.offerId;
  const shopId = root?.dataset.shopId;
  const apiBase = root?.dataset.apiBase || '/api';
  const isAdmin = root?.dataset.isAdmin === 'true';

  const handleRepick = async (orderId: string) => {
    setRepickingOrderId(orderId);
    try {
      const numericOrderId = orderId.replace('gid://shopify/Order/', '');
      await fetchWrapper.post(`${apiBase}/admin/shops/${shopId}/orders/${numericOrderId}/repick`, {});
      // Reload data after repick
      const ordersData = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}/orders`);
      setData(ordersData);
    } catch (err: any) {
      console.error('Failed to repick order:', err);
      setError(err?.error || 'Failed to repick order');
    } finally {
      setRepickingOrderId(null);
    }
  };

  const handleCombine = async (orderId: string) => {
    setCombiningOrderId(orderId);
    try {
      const numericOrderId = orderId.replace('gid://shopify/Order/', '');
      await fetchWrapper.post(`${apiBase}/admin/shops/${shopId}/orders/${numericOrderId}/combine-shipping`, {});
      // Reload data after combine
      const ordersData = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}/orders`);
      setData(ordersData);
    } catch (err: any) {
      console.error('Failed to combine shipping:', err);
      setError(err?.error || 'Failed to combine shipping');
    } finally {
      setCombiningOrderId(null);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      try {
        const ordersData = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}/orders`);
        setData(ordersData);
      } catch (err: any) {
        console.error('Failed to load data:', err);
        setError(err?.error || 'Failed to load orders');
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [apiBase, shopId, offerId]);

  if (loading) {
    return (
      <Container>
        <div className="space-y-6">
          <Skeleton className="h-12 w-2/3 mt-5 mb-3" />
          <div className="text-center py-8 text-muted-foreground">Loading orders from Shopify...</div>
        </div>
      </Container>
    );
  }

  if (error || !data) {
    return (
      <Container>
        <MainTitle>Order Manifests</MainTitle>
        <div className="text-center py-8 text-destructive">{error || 'Failed to load orders'}</div>
        <div className="mt-4">
          <Button variant="outline" asChild>
            <a href={`/shop/${shopId}/offers/${offerId}`}>← Back to Offer Details</a>
          </Button>
        </div>
      </Container>
    );
  }

  return (
    <Container>
      <ShopOfferBreadcrumb
        shopId={shopId!}
        shopName={data.shop?.name}
        offer={{ id: data.offer_id, name: data.offer_name }}
        action="Order Manifests"
      />

      <div className="mb-6">
        <Button variant="outline" asChild>
          <a href={`/shop/${shopId}/offers/${offerId}`}>← Back to Offer Details</a>
        </Button>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">Order Count</div>
          <div className="text-2xl font-semibold">{data.totals.orderCount}</div>
        </div>
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">Total Bottles Sold</div>
          <div className="text-2xl font-semibold">{data.totals.purchasedQty}</div>
        </div>
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">Total Revenue</div>
          <div className="text-2xl font-semibold">{formatCurrency(data.totals.purchasedValue)}</div>
        </div>
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">Total Upgrade Value</div>
          <div className="text-2xl font-semibold">{formatCurrency(data.totals.upgradeValue)}</div>
        </div>
      </div>

      {data.orders.length === 0 ? (
        <div className="text-center py-8 text-muted-foreground border rounded-md">
          No orders found for this offer.
        </div>
      ) : (
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Order</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Shipping Method</TableHead>
                <TableHead className="text-center">Purchased</TableHead>
                <TableHead className="text-center">Upgrades</TableHead>
                <TableHead className="text-center">Consumer Surplus</TableHead>
                <TableHead>Upgrade Items</TableHead>
                {isAdmin && <TableHead className="text-center">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.orders.map((order) => {
                const surplus = order.upgradeValue - order.purchasedValue;
                const surplusPercent = order.purchasedValue > 0 
                  ? ((order.upgradeValue / order.purchasedValue) - 1) * 100 
                  : 0;

                return (
                  <TableRow key={order.id} className={!order.isQtyEqual ? 'bg-yellow-50 dark:bg-yellow-900/10' : ''}>
                    <TableCell>
                      <a
                        href={getShopifyOrderUrl(order.id, data.shop?.shop_domain)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="font-mono text-sm hover:underline flex items-center gap-1"
                      >
                        {order.id.replace('gid://shopify/Order/', '#')}
                        <ExternalLink className="w-3 h-3" />
                      </a>
                      <div className="text-xs text-muted-foreground mt-1 truncate max-w-[150px]">{order.email}</div>
                    </TableCell>
                    <TableCell className="text-sm">
                      {formatDistanceToNow(parseISO(order.createdAt), { addSuffix: true })}
                    </TableCell>
                    <TableCell>
                      {order.displayFulfillmentStatus === 'FULFILLED' ? (
                        <Dialog>
                          <DialogTrigger asChild>
                            <Badge 
                              className="bg-blue-600 hover:bg-blue-700 text-white border-transparent cursor-pointer"
                            >
                              SHIPPED
                            </Badge>
                          </DialogTrigger>
                          <DialogContent>
                            <DialogHeader>
                              <DialogTitle>Tracking Information</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                              {order.fulfillments_nodes.map((f, i) => (
                                <div key={i} className="border-b last:border-0 pb-2 last:pb-0">
                                  <div className="text-sm font-medium mb-1">Fulfillment {i + 1} ({f.status})</div>
                                  {f.trackingInfo.map((t, ti) => (
                                    <div key={ti} className="flex items-center gap-2 text-sm">
                                      <span>{t.number}</span>
                                      {t.url && (
                                        <a href={t.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline flex items-center gap-1">
                                          View Tracking <ExternalLink className="w-3 h-3" />
                                        </a>
                                      )}
                                    </div>
                                  ))}
                                </div>
                              ))}
                              {order.fulfillments_nodes.length === 0 && (
                                <div className="text-sm text-muted-foreground text-center py-4">No tracking information available.</div>
                              )}
                            </div>
                          </DialogContent>
                        </Dialog>
                      ) : (
                        <Badge
                          variant={
                            order.cancelledAt
                              ? 'destructive'
                              : order.displayFinancialStatus === 'PAID'
                                ? 'default'
                                : 'secondary'
                          }
                          className={
                            !order.cancelledAt && order.displayFinancialStatus === 'PAID'
                              ? 'bg-green-600 hover:bg-green-700 text-white border-transparent'
                              : ''
                          }
                        >
                          {order.cancelledAt ? 'CANCELLED' : order.displayFinancialStatus}
                        </Badge>
                      )}
                      {!order.isQtyEqual && (
                        <Badge variant="outline" className="ml-2">
                          QTY MISMATCH
                        </Badge>
                      )}
                    </TableCell>
                    <TableCell>
                      <div className="flex flex-col gap-1">
                        {(order.fulfillmentOrders_nodes || []).map((fo) => (
                          <div key={fo.id} className="text-xs border rounded p-1 bg-muted/50">
                            <div className="font-semibold">{fo.status}</div>
                            <div className="truncate max-w-[150px]" title={fo.deliveryMethod?.presentedName || 'Unknown'}>
                              {fo.deliveryMethod?.presentedName || 'Unknown'}
                            </div>
                          </div>
                        ))}
                        {(!order.fulfillmentOrders_nodes || order.fulfillmentOrders_nodes.length === 0) && (
                          <div className="text-xs text-muted-foreground">No fulfillment data</div>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-center">
                      <div className="font-medium">{order.purchasedQty} btl</div>
                      <div className="text-xs text-muted-foreground">{formatCurrency(order.purchasedValue)}</div>
                    </TableCell>
                    <TableCell className="text-center">
                      <div className="font-medium">{order.upgradeQty} btl</div>
                      <div className="text-xs text-muted-foreground">{formatCurrency(order.upgradeValue)}</div>
                    </TableCell>
                    <TableCell className="text-center">
                      <div className="font-medium text-green-600 dark:text-green-400">
                        {formatCurrency(surplus)}
                      </div>
                      <div className="text-xs text-muted-foreground">
                        {surplusPercent > 0 ? '+' : ''}{surplusPercent.toFixed(0)}%
                      </div>
                    </TableCell>
                    <TableCell className="text-xs max-w-[300px]">
                      {order.upgradeItems.map((item, i) => (
                        <div key={item.line_item_id} className="truncate">
                          {item.currentQuantity}x {item.title}
                        </div>
                      ))}
                    </TableCell>
                    {isAdmin && (
                      <TableCell className="text-center">
                        <div className="flex items-center gap-1 justify-center">
                        {(() => {
                          const isCancelled = !!order.cancelledAt;
                          const isShipped = order.displayFulfillmentStatus === 'FULFILLED';
                          const isRepicking = repickingOrderId === order.id;
                          const isDisabled = isCancelled || isShipped || isRepicking;

                          const getTooltipMessage = () => {
                            if (isCancelled) return "Cannot repick: Order is cancelled";
                            if (isShipped) return "Cannot repick: Order is already shipped";
                            return "Repick manifests for this order";
                          };

                          const button = (
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={isDisabled}
                            >
                              {isRepicking ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                              ) : (
                                <RefreshCw className="h-4 w-4" />
                              )}
                            </Button>
                          );

                          return (
                            <AlertDialog>
                              <Tooltip>
                                <TooltipTrigger asChild>
                                  {isDisabled ? (
                                    <div className="inline-block">{button}</div>
                                  ) : (
                                    <AlertDialogTrigger asChild>
                                      <div className="inline-block">{button}</div>
                                    </AlertDialogTrigger>
                                  )}
                                </TooltipTrigger>
                                <TooltipContent>
                                  {getTooltipMessage()}
                                </TooltipContent>
                              </Tooltip>
                              <AlertDialogContent>
                                <AlertDialogHeader>
                                  <AlertDialogTitle>Repick Order Manifests?</AlertDialogTitle>
                                  <AlertDialogDescription>
                                    This will unassign all {order.upgradeQty} bottle(s) from this order and 
                                    randomly reassign new bottles from the remaining inventory. The customer 
                                    will receive different wines but the same quantity at no additional charge.
                                  </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                  <AlertDialogCancel>Cancel</AlertDialogCancel>
                                  <AlertDialogAction onClick={() => handleRepick(order.id)}>
                                    Repick Bottles
                                  </AlertDialogAction>
                                </AlertDialogFooter>
                              </AlertDialogContent>
                            </AlertDialog>
                          );
                        })()}
                        
                        {(() => {
                           const openGroups = (order.fulfillmentOrders_nodes || []).filter(x => x.status === 'OPEN');
                           const hasMultipleGroups = openGroups.length > 1;
                           const hasSingleGenericGroup = openGroups.length === 1 && 
                             (openGroups[0]?.deliveryMethod?.presentedName === 'Shipping' || !openGroups[0]?.deliveryMethod?.presentedName);
                           
                           const canCombine = hasMultipleGroups || hasSingleGenericGroup;
                           const isCombining = combiningOrderId === order.id;
                           const isDisabled = !canCombine || isCombining;

                           const button = (
                             <Button
                               variant="outline"
                               size="sm"
                               disabled={isDisabled}
                               onClick={() => handleCombine(order.id)}
                             >
                               {isCombining ? <Loader2 className="h-4 w-4 animate-spin" /> : "Combine"}
                             </Button>
                           );

                           return (
                             <Tooltip>
                               <TooltipTrigger asChild>
                                 <div className="inline-block">{button}</div>
                               </TooltipTrigger>
                               <TooltipContent>
                                 {hasMultipleGroups ? "Combine shipping groups" : hasSingleGenericGroup ? "Fix 'Shipping' method name" : "Nothing to combine"}
                               </TooltipContent>
                             </Tooltip>
                           );
                        })()}
                        </div>
                      </TableCell>
                    )}
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </div>
      )}
    </Container>
  );
}

const element = document.getElementById('offer-manifests-root');
if (element) {
  createRoot(element).render(<ShopifyManifestsPage />);
}
