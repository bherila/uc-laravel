import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { fetchWrapper } from '@/fetchWrapper';
import { ArrowLeft, Save, Plus, Trash2 } from 'lucide-react';

interface ShopAccess {
  id: number;
  shopify_shop_id: number;
  access_level: 'read-only' | 'read-write';
  shop?: {
    id: number;
    name: string;
    shop_domain: string;
  };
}

interface User {
  id: number;
  email: string;
  alias: string | null;
  is_admin: boolean;
  last_login_at: string | null;
  created_at: string;
  shop_accesses: ShopAccess[];
}

interface Shop {
  id: number;
  name: string;
  shop_domain: string;
}

function AdminUserDetailPage() {
  const [user, setUser] = useState<User | null>(null);
  const [shops, setShops] = useState<Shop[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const [formData, setFormData] = useState({
    email: '',
    alias: '',
    password: '',
    is_admin: false,
  });
  const [shopAccesses, setShopAccesses] = useState<{ shopify_shop_id: number; access_level: 'read-only' | 'read-write' }[]>([]);
  
  const rootEl = document.getElementById('admin-user-detail-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const userId = rootEl?.dataset.userId;

  const fetchUser = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/users/${userId}`);
      setUser(data);
      setFormData({
        email: data.email,
        alias: data.alias || '',
        password: '',
        is_admin: data.is_admin,
      });
      setShopAccesses(data.shop_accesses.map((a: ShopAccess) => ({
        shopify_shop_id: a.shopify_shop_id,
        access_level: a.access_level,
      })));
    } catch (err) {
      setError('Failed to load user');
      console.error(err);
    }
  }, [apiBase, userId]);

  const fetchShops = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/stores`);
      setShops(data);
    } catch (err) {
      console.error('Failed to load shops:', err);
    }
  }, [apiBase]);

  useEffect(() => {
    Promise.all([fetchUser(), fetchShops()]).finally(() => setLoading(false));
  }, [fetchUser, fetchShops]);

  const saveUser = async () => {
    setSaving(true);
    try {
      // Update user info
      const updateData: any = {
        email: formData.email,
        alias: formData.alias || null,
        is_admin: formData.is_admin,
      };
      if (formData.password) {
        updateData.password = formData.password;
      }
      await fetchWrapper.put(`${apiBase}/admin/users/${userId}`, updateData);

      // Update shop accesses
      await fetchWrapper.put(`${apiBase}/admin/users/${userId}/shop-accesses`, {
        shop_accesses: shopAccesses,
      });

      window.location.href = '/admin/users';
    } catch (err: any) {
      console.error('Failed to save user:', err);
      alert(err?.error || 'Failed to save user');
    } finally {
      setSaving(false);
    }
  };

  const addShopAccess = () => {
    const usedShopIds = shopAccesses.map(a => a.shopify_shop_id);
    const availableShop = shops.find(s => !usedShopIds.includes(s.id));
    if (availableShop) {
      setShopAccesses([...shopAccesses, { shopify_shop_id: availableShop.id, access_level: 'read-only' }]);
    }
  };

  const removeShopAccess = (index: number) => {
    setShopAccesses(shopAccesses.filter((_, i) => i !== index));
  };

  const updateShopAccess = (index: number, field: 'shopify_shop_id' | 'access_level', value: any) => {
    setShopAccesses(prev => {
      const updated = [...prev];
      const current = updated[index];
      if (!current) return prev;
      
      if (field === 'shopify_shop_id') {
        updated[index] = { ...current, shopify_shop_id: Number(value) };
      } else {
        updated[index] = { ...current, access_level: value };
      }
      return updated;
    });
  };

  if (loading) {
    return (
      <Container>
        <MainTitle>Edit User</MainTitle>
        <Skeleton className="h-96 w-full" />
      </Container>
    );
  }

  if (error || !user) {
    return (
      <Container>
        <MainTitle>Edit User</MainTitle>
        <div className="text-red-600 dark:text-red-400">{error || 'User not found'}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="mb-6">
        <Button variant="ghost" size="sm" asChild>
          <a href="/admin/users">
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Users
          </a>
        </Button>
      </div>

      <MainTitle>Edit User: {user.email}</MainTitle>
      
      <div className="space-y-8 max-w-2xl">
        {/* User Info */}
        <div className="space-y-4 p-6 border rounded-lg">
          <h2 className="text-lg font-semibold">User Information</h2>
          
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input 
              id="email" 
              type="email" 
              value={formData.email} 
              onChange={(e) => setFormData({ ...formData, email: e.target.value })} 
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="alias">Alias</Label>
            <Input 
              id="alias" 
              value={formData.alias} 
              onChange={(e) => setFormData({ ...formData, alias: e.target.value })} 
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="password">New Password (leave blank to keep current)</Label>
            <Input 
              id="password" 
              type="password" 
              value={formData.password} 
              onChange={(e) => setFormData({ ...formData, password: e.target.value })} 
            />
          </div>
          
          <div className="flex items-center gap-2">
            <input 
              type="checkbox" 
              id="is_admin" 
              checked={formData.is_admin} 
              onChange={(e) => setFormData({ ...formData, is_admin: e.target.checked })} 
              disabled={user.id === 1}
            />
            <Label htmlFor="is_admin">Admin</Label>
            {user.id === 1 && <span className="text-sm text-gray-500">(Cannot change for user ID 1)</span>}
          </div>
        </div>

        {/* Shop Access */}
        <div className="space-y-4 p-6 border rounded-lg">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold">Shop Access</h2>
            <Button 
              variant="outline" 
              size="sm" 
              onClick={addShopAccess}
              disabled={shopAccesses.length >= shops.length}
            >
              <Plus className="w-4 h-4 mr-2" />
              Add Access
            </Button>
          </div>
          
          {shopAccesses.length === 0 ? (
            <p className="text-gray-500 text-sm">No shop access configured. {user.is_admin && 'Admins have access to all shops.'}</p>
          ) : (
            <div className="space-y-3">
              {shopAccesses.map((access, index) => (
                <div key={index} className="flex items-center gap-3">
                  <Select 
                    value={String(access.shopify_shop_id)} 
                    onValueChange={(v) => updateShopAccess(index, 'shopify_shop_id', v)}
                  >
                    <SelectTrigger className="flex-1">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {shops.map((shop) => (
                        <SelectItem key={shop.id} value={String(shop.id)}>
                          {shop.name} ({shop.shop_domain})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <Select 
                    value={access.access_level} 
                    onValueChange={(v) => updateShopAccess(index, 'access_level', v)}
                  >
                    <SelectTrigger className="w-40">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="read-only">Read Only</SelectItem>
                      <SelectItem value="read-write">Read/Write</SelectItem>
                    </SelectContent>
                  </Select>
                  <Button variant="ghost" size="sm" onClick={() => removeShopAccess(index)}>
                    <Trash2 className="w-4 h-4 text-red-600" />
                  </Button>
                </div>
              ))}
            </div>
          )}
        </div>

        <Button onClick={saveUser} disabled={saving} className="w-full">
          <Save className="w-4 h-4 mr-2" />
          {saving ? 'Saving...' : 'Save Changes'}
        </Button>
      </div>
    </Container>
  );
}

const root = document.getElementById('admin-user-detail-root');
if (root) {
  createRoot(root).render(<AdminUserDetailPage />);
}
