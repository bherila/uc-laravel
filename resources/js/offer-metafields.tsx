import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import ShopOfferBreadcrumb from '@/components/ShopOfferBreadcrumb';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { fetchWrapper } from '@/fetchWrapper';

interface Metafields {
  offerV3: string;
  offerV3Array: string;
}

function MetafieldsPage() {
  const [metafields, setMetafields] = useState<Metafields | null>(null);
  const [loading, setLoading] = useState(true);
  const [pushing, setPushing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [offer, setOffer] = useState<any>(null);

  const root = document.getElementById('offer-metafields-root');
  const offerId = root?.dataset.offerId;
  const shopId = root?.dataset.shopId;
  const apiBase = root?.dataset.apiBase || '/api';

  const handlePushMetafields = async () => {
    setPushing(true);
    setError(null);
    try {
      const metafieldsData = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}/metafields`);
      setMetafields(metafieldsData);
    } catch (err: any) {
      console.error('Failed to push metafields:', err);
      setError(err?.error || 'Failed to push metafields');
    } finally {
      setPushing(false);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      try {
        // Load offer details and update metafields
        const [offerData, metafieldsData] = await Promise.all([
          fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}`),
          fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/${offerId}/metafields`),
        ]);
        setOffer(offerData);
        setMetafields(metafieldsData);
      } catch (err: any) {
        console.error('Failed to load data:', err);
        setError(err?.error || 'Failed to load metafields');
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
          <div className="text-center py-8 text-muted-foreground">Loading and updating metafields...</div>
        </div>
      </Container>
    );
  }

  if (error || !offer) {
    return (
      <Container>
        <MainTitle>Metafields</MainTitle>
        <div className="text-center py-8 text-destructive">{error || 'Offer not found'}</div>
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
        shopName={offer.shop?.name} 
        offer={{ id: offer.offer_id, name: offer.offer_name }}
        action="Metafields"
      />

      <div className="mb-6 flex justify-between items-center">
        <Button variant="outline" asChild>
          <a href={`/shop/${shopId}/offers/${offerId}`}>← Back to Offer Details</a>
        </Button>
        <Button 
          onClick={handlePushMetafields} 
          disabled={pushing || loading}
        >
          {pushing ? 'Pushing...' : 'Push Metafields to Shopify'}
        </Button>
      </div>

      {metafields ? (
        <div className="space-y-6 max-w-4xl">
          <div className="space-y-2">
            <Label htmlFor="offerV3">offer_v3</Label>
            <Textarea
              id="offerV3"
              value={metafields.offerV3}
              readOnly
              className="font-mono text-xs h-[300px]"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="offerV3Array">offer_v3_array</Label>
            <Textarea
              id="offerV3Array"
              value={metafields.offerV3Array}
              readOnly
              className="font-mono text-xs h-[300px]"
            />
          </div>

          <p className="text-sm text-muted-foreground">
            These metafields have been updated in Shopify on the product associated with this offer.
          </p>
        </div>
      ) : (
        <p className="text-muted-foreground">No metafields found for this offer.</p>
      )}
    </Container>
  );
}

const element = document.getElementById('offer-metafields-root');
if (element) {
  createRoot(element).render(<MetafieldsPage />);
}
