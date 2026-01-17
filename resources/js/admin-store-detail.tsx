import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fetchWrapper } from '@/fetchWrapper';
import { ArrowLeft, Save } from 'lucide-react';

interface Store {
  id: number;
  name: string;
  shop_domain: string;
  app_name: string | null;
  admin_api_token: string | null;
  api_version: string;
  api_key: string | null;
  api_secret_key: string | null;
  webhook_version: string;
  webhook_secret: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

function AdminStoreDetailPage() {
  const [store, setStore] = useState<Store | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const [formData, setFormData] = useState({
    name: '',
    shop_domain: '',
    app_name: '',
    admin_api_token: '',
    api_version: '',
    api_key: '',
    api_secret_key: '',
    webhook_version: '',
    webhook_secret: '',
    is_active: true,
  });
  
  const rootEl = document.getElementById('admin-store-detail-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const storeId = rootEl?.dataset.storeId;

  const fetchStore = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/stores/${storeId}`);
      setStore(data);
      setFormData({
        name: data.name || '',
        shop_domain: data.shop_domain || '',
        app_name: data.app_name || '',
        admin_api_token: data.admin_api_token || '',
        api_version: data.api_version || '2025-01',
        api_key: data.api_key || '',
        api_secret_key: data.api_secret_key || '',
        webhook_version: data.webhook_version || '2025-01',
        webhook_secret: data.webhook_secret || '',
        is_active: data.is_active,
      });
    } catch (err) {
      setError('Failed to load store');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase, storeId]);

  useEffect(() => {
    fetchStore();
  }, [fetchStore]);

  const saveStore = async () => {
    setSaving(true);
    try {
      await fetchWrapper.put(`${apiBase}/admin/stores/${storeId}`, {
        name: formData.name,
        shop_domain: formData.shop_domain,
        app_name: formData.app_name || null,
        admin_api_token: formData.admin_api_token || null,
        api_version: formData.api_version,
        api_key: formData.api_key || null,
        api_secret_key: formData.api_secret_key || null,
        webhook_version: formData.webhook_version,
        webhook_secret: formData.webhook_secret || null,
        is_active: formData.is_active,
      });
      alert('Store saved successfully');
      fetchStore();
    } catch (err: any) {
      console.error('Failed to save store:', err);
      alert(err?.error || 'Failed to save store');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <Container>
        <MainTitle>Edit Store</MainTitle>
        <Skeleton className="h-96 w-full" />
      </Container>
    );
  }

  if (error || !store) {
    return (
      <Container>
        <MainTitle>Edit Store</MainTitle>
        <div className="text-red-600 dark:text-red-400">{error || 'Store not found'}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="mb-6">
        <Button variant="ghost" size="sm" asChild>
          <a href="/admin/stores">
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Stores
          </a>
        </Button>
      </div>

      <MainTitle>Edit Store: {store.name}</MainTitle>
      
      <div className="space-y-8 max-w-2xl">
        {/* Basic Info */}
        <div className="space-y-4 p-6 border rounded-lg">
          <h2 className="text-lg font-semibold">Basic Information</h2>
          
          <div className="space-y-2">
            <Label htmlFor="name">Store Name</Label>
            <Input 
              id="name" 
              value={formData.name} 
              onChange={(e) => setFormData({ ...formData, name: e.target.value })} 
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="shop_domain">Shop Domain</Label>
            <Input 
              id="shop_domain" 
              value={formData.shop_domain} 
              onChange={(e) => setFormData({ ...formData, shop_domain: e.target.value })} 
              placeholder="my-store.myshopify.com"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="app_name">App Name (optional)</Label>
            <Input 
              id="app_name" 
              value={formData.app_name} 
              onChange={(e) => setFormData({ ...formData, app_name: e.target.value })} 
            />
          </div>
          
          <div className="flex items-center gap-2">
            <input 
              type="checkbox" 
              id="is_active" 
              checked={formData.is_active} 
              onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })} 
            />
            <Label htmlFor="is_active">Active</Label>
          </div>
        </div>

        {/* API Configuration */}
        <div className="space-y-4 p-6 border rounded-lg">
          <h2 className="text-lg font-semibold">API Configuration</h2>
          
          <div className="space-y-2">
            <Label htmlFor="admin_api_token">Admin API Token</Label>
            <Input 
              id="admin_api_token" 
              type="password"
              value={formData.admin_api_token} 
              onChange={(e) => setFormData({ ...formData, admin_api_token: e.target.value })} 
              placeholder="shpat_..."
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="api_version">API Version</Label>
            <Input 
              id="api_version" 
              value={formData.api_version} 
              onChange={(e) => setFormData({ ...formData, api_version: e.target.value })} 
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="api_key">API Key (optional)</Label>
              <Input 
                id="api_key" 
                type="password"
                value={formData.api_key} 
                onChange={(e) => setFormData({ ...formData, api_key: e.target.value })} 
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="api_secret_key">API Secret Key (optional)</Label>
              <Input 
                id="api_secret_key" 
                type="password"
                value={formData.api_secret_key} 
                onChange={(e) => setFormData({ ...formData, api_secret_key: e.target.value })} 
              />
            </div>
          </div>
        </div>

        {/* Webhook Configuration */}
        <div className="space-y-4 p-6 border rounded-lg">
          <h2 className="text-lg font-semibold">Webhook Configuration</h2>
          
          <div className="space-y-2">
            <Label htmlFor="webhook_version">Webhook Version</Label>
            <Input 
              id="webhook_version" 
              value={formData.webhook_version} 
              onChange={(e) => setFormData({ ...formData, webhook_version: e.target.value })} 
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="webhook_secret">Webhook Secret</Label>
            <Input 
              id="webhook_secret" 
              type="password"
              value={formData.webhook_secret} 
              onChange={(e) => setFormData({ ...formData, webhook_secret: e.target.value })} 
            />
          </div>
        </div>

        <Button onClick={saveStore} disabled={saving} className="w-full">
          <Save className="w-4 h-4 mr-2" />
          {saving ? 'Saving...' : 'Save Changes'}
        </Button>
      </div>
    </Container>
  );
}

const root = document.getElementById('admin-store-detail-root');
if (root) {
  createRoot(root).render(<AdminStoreDetailPage />);
}
