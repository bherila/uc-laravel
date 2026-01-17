import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { fetchWrapper } from '@/fetchWrapper';
import { formatCurrency } from '@/lib/currency';
import { formatDistanceToNow, parseISO } from 'date-fns';

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
}

interface OfferOrders {
  offer_id: number;
  offer_name: string;
  variant_id: string;
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

function getShopifyOrderUrl(orderId: string): string {
  const numericId = orderId.replace('gid://shopify/Order/', '');
  return `https://admin.shopify.com/store/underground-cellar/orders/${numericId}`;
}

function ShopifyManifestsPage() {
  const [data, setData] = useState<OfferOrders | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const root = document.getElementById('offer-manifests-root');
  const offerId = root?.dataset.offerId;
  const apiBase = root?.dataset.apiBase || '/api';

  useEffect(() => {
    const loadData = async () => {
      try {
        const ordersData = await fetchWrapper.get(`${apiBase}/offers/${offerId}/orders`);
        setData(ordersData);
      } catch (err: any) {
        console.error('Failed to load data:', err);
        setError(err?.error || 'Failed to load orders');
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [apiBase, offerId]);

  if (loading) {
    return (
      <Container>
        <MainTitle>Order Manifests</MainTitle>
        <div className="text-center py-8 text-muted-foreground">Loading orders from Shopify...</div>
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
            <a href={`/offers/${offerId}`}>← Back to Offer Details</a>
          </Button>
        </div>
      </Container>
    );
  }

  return (
    <Container>
      <MainTitle>Order Manifests for [{data.offer_id}] {data.offer_name}</MainTitle>

      <div className="mb-6">
        <Button variant="outline" asChild>
          <a href={`/offers/${offerId}`}>← Back to Offer Details</a>
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
                <TableHead>Customer</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-center">Purchased</TableHead>
                <TableHead className="text-center">Upgrades</TableHead>
                <TableHead>Upgrade Items</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.orders.map((order) => (
                <TableRow key={order.id} className={!order.isQtyEqual ? 'bg-yellow-50 dark:bg-yellow-900/10' : ''}>
                  <TableCell>
                    <a
                      href={getShopifyOrderUrl(order.id)}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="font-mono text-sm hover:underline"
                    >
                      {order.id.replace('gid://shopify/Order/', '#')}
                    </a>
                  </TableCell>
                  <TableCell className="text-sm">
                    {formatDistanceToNow(parseISO(order.createdAt), { addSuffix: true })}
                  </TableCell>
                  <TableCell className="text-sm truncate max-w-[200px]">{order.email}</TableCell>
                  <TableCell>
                    <Badge
                      variant={
                        order.cancelledAt
                          ? 'destructive'
                          : order.displayFinancialStatus === 'PAID'
                          ? 'default'
                          : 'secondary'
                      }
                    >
                      {order.cancelledAt ? 'CANCELLED' : order.displayFinancialStatus}
                    </Badge>
                    {!order.isQtyEqual && (
                      <Badge variant="outline" className="ml-2">
                        QTY MISMATCH
                      </Badge>
                    )}
                  </TableCell>
                  <TableCell className="text-center">
                    <div className="font-medium">{order.purchasedQty} btl</div>
                    <div className="text-xs text-muted-foreground">{formatCurrency(order.purchasedValue)}</div>
                  </TableCell>
                  <TableCell className="text-center">
                    <div className="font-medium">{order.upgradeQty} btl</div>
                    <div className="text-xs text-muted-foreground">{formatCurrency(order.upgradeValue)}</div>
                  </TableCell>
                  <TableCell className="text-xs max-w-[300px]">
                    {order.upgradeItems.map((item, i) => (
                      <div key={item.line_item_id} className="truncate">
                        {item.title} x{item.currentQuantity}
                      </div>
                    ))}
                  </TableCell>
                </TableRow>
              ))}
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
