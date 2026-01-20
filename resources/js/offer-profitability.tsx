import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useMemo } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { formatCurrency } from '@/lib/currency';

interface ProductData {
  title?: string;
  priceRange?: {
    maxVariantPrice?: { amount: string };
  };
  inventoryItem?: {
    unitCost?: { amount: string };
  };
}

interface ManifestGroup {
  total: number;
}

interface OfferDetail {
  offer_id: number;
  offer_name: string;
  shop_id: number;
  shop?: {
    id: number;
    name: string;
    shop_domain: string;
  };
  offerProductData: {
    priceRange?: {
      maxVariantPrice?: { amount: string };
    };
  } | null;
  manifestGroups: Record<string, ManifestGroup>;
  manifestProductData: Record<string, ProductData>;
}

interface ProductProfit {
  variantId: string;
  title: string;
  qty: number;
  retailPrice: number;
  unitCost: number;
  revenue: number;
  cost: number;
  profit: number;
  marginPercent: number;
  profitPerUnit: number;
}

interface SellThroughScenario {
  quantity: number;
  bestCaseProfit: number;
  worstCaseProfit: number;
  sellThroughPercent: number;
}

function ProfitabilityPage() {
  const [offer, setOffer] = useState<OfferDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showEvery3Qty, setShowEvery3Qty] = useState(true);

  const root = document.getElementById('offer-profitability-root');
  const offerId = root?.dataset.offerId;
  const shopId = root?.dataset.shopId;
  const apiBase = root?.dataset.apiBase || '/api';

  useEffect(() => {
    const loadData = async () => {
      try {
        const data = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}?detail=1`);
        setOffer(data);
      } catch (err) {
        console.error('Failed to load data:', err);
        setError('Failed to load offer data');
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [apiBase, shopId, offerId]);

  const offerPrice = useMemo(() => {
    return parseFloat(offer?.offerProductData?.priceRange?.maxVariantPrice?.amount ?? '0');
  }, [offer]);

  const { profitByProduct, totalRevenue, totalCost, totalProfit, totalMarginPercent } = useMemo(() => {
    if (!offer) {
      return { profitByProduct: [], totalRevenue: 0, totalCost: 0, totalProfit: 0, totalMarginPercent: 0 };
    }

    let totalRevenue = 0;
    let totalCost = 0;

    const profitByProduct: ProductProfit[] = Object.entries(offer.manifestGroups).map(([variantId, group]) => {
      const product = offer.manifestProductData[variantId];
      const qty = group.total;
      const revenue = offerPrice * qty;
      const retailPrice = parseFloat(product?.priceRange?.maxVariantPrice?.amount ?? '0');
      const unitCost = parseFloat(product?.inventoryItem?.unitCost?.amount ?? '0');
      const profitPerUnit = offerPrice - unitCost;
      const cost = unitCost * qty;
      const profit = revenue - cost;
      const marginPercent = revenue > 0 ? (profit / revenue) * 100 : 0;

      totalRevenue += revenue;
      totalCost += cost;

      return {
        variantId,
        title: product?.title ?? '??',
        qty,
        retailPrice,
        unitCost,
        revenue,
        cost,
        profit,
        marginPercent,
        profitPerUnit,
      };
    });

    const totalProfit = totalRevenue - totalCost;
    const totalMarginPercent = totalRevenue > 0 ? (totalProfit / totalRevenue) * 100 : 0;

    return { profitByProduct, totalRevenue, totalCost, totalProfit, totalMarginPercent };
  }, [offer, offerPrice]);

  const { sellThroughScenarios, minQuantityForProfit, minQuantityPercent, bestCaseScenario, worstCaseScenario, totalQuantity } = useMemo(() => {
    const totalQuantity = profitByProduct.reduce((sum, p) => sum + p.qty, 0);
    const sortedByProfitPerUnit = [...profitByProduct].sort((a, b) => b.profitPerUnit - a.profitPerUnit);

    const scenarios: SellThroughScenario[] = [];

    for (let sellQty = 0; sellQty <= totalQuantity; sellQty++) {
      let bestCaseProfit = 0;
      let worstCaseProfit = 0;
      let remainingQty = sellQty;

      // Best case: sell highest margin products first
      for (const product of sortedByProfitPerUnit) {
        const qtyToSell = Math.min(remainingQty, product.qty);
        if (qtyToSell > 0) {
          bestCaseProfit += qtyToSell * product.profitPerUnit;
          remainingQty -= qtyToSell;
        }
      }

      // Worst case: sell lowest margin products first
      remainingQty = sellQty;
      for (const product of [...sortedByProfitPerUnit].reverse()) {
        const qtyToSell = Math.min(remainingQty, product.qty);
        if (qtyToSell > 0) {
          worstCaseProfit += qtyToSell * product.profitPerUnit;
          remainingQty -= qtyToSell;
        }
      }

      scenarios.push({
        quantity: sellQty,
        bestCaseProfit,
        worstCaseProfit,
        sellThroughPercent: (sellQty / totalQuantity) * 100,
      });
    }

    const minQuantityForProfit = scenarios.find((s) => s.worstCaseProfit > 0)?.quantity ?? totalQuantity;
    const minQuantityPercent = totalQuantity > 0 ? (minQuantityForProfit / totalQuantity) * 100 : 0;

    const bestCaseScenario = scenarios.reduce((best, current) =>
      current.bestCaseProfit > best.bestCaseProfit ? current : best,
      scenarios[0] ?? { quantity: 0, bestCaseProfit: 0, worstCaseProfit: 0, sellThroughPercent: 0 }
    );
    const worstCaseScenario = scenarios.reduce((worst, current) =>
      current.worstCaseProfit < worst.worstCaseProfit ? current : worst,
      scenarios[0] ?? { quantity: 0, bestCaseProfit: 0, worstCaseProfit: 0, sellThroughPercent: 0 }
    );

    return { sellThroughScenarios: scenarios, minQuantityForProfit, minQuantityPercent, bestCaseScenario, worstCaseScenario, totalQuantity };
  }, [profitByProduct]);

  const filteredScenarios = useMemo(() => {
    return showEvery3Qty
      ? sellThroughScenarios.filter((s) => s.quantity % 3 === 0)
      : sellThroughScenarios;
  }, [sellThroughScenarios, showEvery3Qty]);

  if (loading) {
    return (
      <Container>
        <div className="space-y-6">
          <Skeleton className="h-12 w-2/3 mt-5 mb-3" />
          <Skeleton className="h-9 w-48" />
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Skeleton className="h-32 w-full" />
            <Skeleton className="h-32 w-full" />
            <Skeleton className="h-32 w-full" />
          </div>
          <div className="space-y-4">
            <Skeleton className="h-8 w-1/4" />
            <div className="rounded-md border p-4 space-y-2">
              <Skeleton className="h-10 w-full" />
              <Skeleton className="h-10 w-full" />
              <Skeleton className="h-10 w-full" />
            </div>
          </div>
        </div>
      </Container>
    );
  }

  if (error || !offer) {
    return (
      <Container>
        <MainTitle>Profitability</MainTitle>
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
        action="Profitability"
      />

      <div className="mb-6">
        <Button variant="outline" asChild>
          <a href={`/shop/${shopId}/offers/${offerId}`}>← Back to Offer Details</a>
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="space-y-2">
          <h3 className="font-semibold text-lg">Summary</h3>
          <div className="text-sm space-y-1">
            <p>Total Revenue: {formatCurrency(totalRevenue)}</p>
            <p>Total Cost: {formatCurrency(totalCost)}</p>
            <p>Total Profit: {formatCurrency(totalProfit)}</p>
            <p>Overall Margin: {totalMarginPercent.toFixed(2)}%</p>
          </div>
        </div>
        <div className="space-y-2">
          <h3 className="font-semibold text-lg">Break-Even</h3>
          <div className="text-sm space-y-1">
            <p>Min Quantity for Profit: {minQuantityForProfit} ({minQuantityPercent.toFixed(1)}% sell-through)</p>
          </div>
        </div>
        <div className="space-y-2">
          <h3 className="font-semibold text-lg">Scenarios</h3>
          <div className="text-sm space-y-1">
            <p>Best Case Profit: {formatCurrency(bestCaseScenario.bestCaseProfit)} at Qty {bestCaseScenario.quantity}</p>
            <p>Worst Case Profit: {formatCurrency(worstCaseScenario.worstCaseProfit)} at Qty {worstCaseScenario.quantity}</p>
          </div>
        </div>
      </div>

      <h3 className="font-semibold text-lg mb-4">Product Breakdown</h3>
      <div className="rounded-md border mb-8">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Product</TableHead>
              <TableHead>Quantity</TableHead>
              <TableHead>Retail Price</TableHead>
              <TableHead>Unit Cost</TableHead>
              <TableHead>Total Revenue<br /><span className="text-xs font-normal">(offer price {formatCurrency(offerPrice)})</span></TableHead>
              <TableHead>Total Cost</TableHead>
              <TableHead>Total Profit</TableHead>
              <TableHead>Margin %</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {profitByProduct.map((item) => (
              <TableRow key={item.variantId}>
                <TableCell>{item.title}</TableCell>
                <TableCell>{item.qty}</TableCell>
                <TableCell>{formatCurrency(item.retailPrice)}</TableCell>
                <TableCell>{formatCurrency(item.unitCost)}</TableCell>
                <TableCell>{formatCurrency(item.revenue)}</TableCell>
                <TableCell>{formatCurrency(item.cost)}</TableCell>
                <TableCell>{formatCurrency(item.profit)}</TableCell>
                <TableCell>{item.marginPercent.toFixed(2)}%</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      <h3 className="font-semibold text-lg mb-4">Sell-Through Scenarios</h3>
      <div className="flex items-center space-x-2 mb-4">
        <Checkbox
          id="showEvery3"
          checked={showEvery3Qty}
          onCheckedChange={(checked) => setShowEvery3Qty(checked === true)}
        />
        <Label htmlFor="showEvery3">Show only every 3rd quantity</Label>
      </div>
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Quantity Sold</TableHead>
              <TableHead>Sell-Through %</TableHead>
              <TableHead>Best Case Profit</TableHead>
              <TableHead>Worst Case Profit</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {filteredScenarios.map((scenario) => (
              <TableRow key={scenario.quantity}>
                <TableCell>{scenario.quantity}</TableCell>
                <TableCell>{scenario.sellThroughPercent.toFixed(1)}%</TableCell>
                <TableCell>{formatCurrency(scenario.bestCaseProfit)}</TableCell>
                <TableCell>{formatCurrency(scenario.worstCaseProfit)}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </Container>
  );
}

const element = document.getElementById('offer-profitability-root');
if (element) {
  createRoot(element).render(<ProfitabilityPage />);
}
