import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useMemo } from 'react';
import Container from '@/components/container';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { formatCurrency } from '@/lib/currency';
import { AlertCircle, CheckCircle2, XCircle } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  ManifestOrdersTable,
  type Order
} from '@/components/ManifestOrdersTable';

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
    totalManifests?: number;
  };
}

function ShopifyManifestsPage() {
  const [data, setData] = useState<OfferOrders | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [repickingOrderId, setRepickingOrderId] = useState<string | null>(null);
  const [combiningOrderId, setCombiningOrderId] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<string>('paid');

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

  const sortedOrders = useMemo(() => {
    if (!data?.orders) return [];
    return [...data.orders].sort((a, b) =>
      new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    );
  }, [data?.orders]);

  const groupedOrders = useMemo(() => {
    const issues = sortedOrders.filter(o => !o.isQtyEqual);
    const canceled = sortedOrders.filter(o => !!o.cancelledAt || o.displayFinancialStatus === 'REFUNDED');
    const paid = sortedOrders.filter(o =>
      o.displayFinancialStatus === 'PAID' &&
      o.isQtyEqual &&
      !o.cancelledAt
    );

    return { issues, paid, canceled };
  }, [sortedOrders]);

  useEffect(() => {
    if (loading === false && groupedOrders.issues.length > 0) {
      setActiveTab('issues');
    }
  }, [loading, groupedOrders.issues.length > 0]);

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
          <div className="text-2xl font-semibold">
            {data.totals.totalManifests !== undefined
              ? `${data.totals.purchasedQty} of ${data.totals.totalManifests}`
              : data.totals.purchasedQty}
          </div>
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

      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="mb-4">
          <TabsTrigger value="issues" className="flex gap-2 items-center">
            <AlertCircle className="w-4 h-4 text-amber-500" />
            Issues
            <Badge variant="secondary" className="ml-1 h-5 px-1.5">{groupedOrders.issues.length}</Badge>
          </TabsTrigger>
          <TabsTrigger value="paid" className="flex gap-2 items-center">
            <CheckCircle2 className="w-4 h-4 text-green-500" />
            Paid
            <Badge variant="secondary" className="ml-1 h-5 px-1.5">{groupedOrders.paid.length}</Badge>
          </TabsTrigger>
          <TabsTrigger value="canceled" className="flex gap-2 items-center">
            <XCircle className="w-4 h-4 text-red-500" />
            Canceled/Refunded
            <Badge variant="secondary" className="ml-1 h-5 px-1.5">{groupedOrders.canceled.length}</Badge>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="issues">
          {groupedOrders.issues.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground border rounded-md bg-muted/20">
              No orders with inventory issues found.
            </div>
          ) : (
            <ManifestOrdersTable
              orders={groupedOrders.issues}
              isAdmin={isAdmin}
              shopDomain={data.shop?.shop_domain}
              repickingOrderId={repickingOrderId}
              combiningOrderId={combiningOrderId}
              handleRepick={handleRepick}
              handleCombine={handleCombine}
            />
          )}
        </TabsContent>

        <TabsContent value="paid">
          {groupedOrders.paid.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground border rounded-md bg-muted/20">
              No regular paid orders found.
            </div>
          ) : (
            <ManifestOrdersTable
              orders={groupedOrders.paid}
              isAdmin={isAdmin}
              shopDomain={data.shop?.shop_domain}
              repickingOrderId={repickingOrderId}
              combiningOrderId={combiningOrderId}
              handleRepick={handleRepick}
              handleCombine={handleCombine}
            />
          )}
        </TabsContent>

        <TabsContent value="canceled">
          {groupedOrders.canceled.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground border rounded-md bg-muted/20">
              No canceled or refunded orders found.
            </div>
          ) : (
            <ManifestOrdersTable
              orders={groupedOrders.canceled}
              isAdmin={isAdmin}
              shopDomain={data.shop?.shop_domain}
              repickingOrderId={repickingOrderId}
              combiningOrderId={combiningOrderId}
              handleRepick={handleRepick}
              handleCombine={handleCombine}
            />
          )}
        </TabsContent>
      </Tabs>
    </Container>
  );
}

const element = document.getElementById('offer-manifests-root');
if (element) {
  createRoot(element).render(<ShopifyManifestsPage />);
}
