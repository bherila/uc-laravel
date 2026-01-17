import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { fetchWrapper } from '@/fetchWrapper';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { Plus, Edit, Trash2, Store, Check, X } from 'lucide-react';

interface Shop {
  id: number;
  name: string;
  shop_domain: string;
  is_active: boolean;
  api_version: string;
  offers_count: number;
  users_count: number;
  created_at: string;
}

function AdminStoresPage() {
  const [stores, setStores] = useState<Shop[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [creating, setCreating] = useState(false);
  const [newStore, setNewStore] = useState({ 
    name: '', 
    shop_domain: '', 
    admin_api_token: '',
    api_version: '2025-01',
    webhook_secret: '',
    is_active: true 
  });
  
  const apiBase = document.getElementById('admin-stores-root')?.dataset.apiBase || '/api';

  const fetchStores = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/stores`);
      setStores(data);
    } catch (err) {
      setError('Failed to load stores');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  useEffect(() => {
    fetchStores();
  }, [fetchStores]);

  const createStore = async () => {
    setCreating(true);
    try {
      await fetchWrapper.post(`${apiBase}/admin/stores`, newStore);
      setShowCreateDialog(false);
      setNewStore({ 
        name: '', 
        shop_domain: '', 
        admin_api_token: '',
        api_version: '2025-01',
        webhook_secret: '',
        is_active: true 
      });
      fetchStores();
    } catch (err) {
      console.error('Failed to create store:', err);
      alert('Failed to create store');
    } finally {
      setCreating(false);
    }
  };

  const deleteStore = async (id: number) => {
    if (!confirm('Are you sure you want to delete this store? This cannot be undone.')) return;
    try {
      await fetchWrapper.delete(`${apiBase}/admin/stores/${id}`, {});
      fetchStores();
    } catch (err: any) {
      console.error('Failed to delete store:', err);
      alert(err?.error || 'Failed to delete store');
    }
  };

  if (loading) {
    return (
      <Container>
        <MainTitle>Admin: Stores</MainTitle>
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-16 w-full" />
          ))}
        </div>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <MainTitle>Admin: Stores</MainTitle>
        <div className="text-red-600 dark:text-red-400">{error}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="flex items-center justify-between mb-6">
        <MainTitle>Admin: Stores</MainTitle>
        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="w-4 h-4 mr-2" />
              Add Store
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>Create New Store</DialogTitle>
              <DialogDescription>Add a new Shopify store to the system.</DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="name">Store Name</Label>
                <Input 
                  id="name" 
                  value={newStore.name} 
                  onChange={(e) => setNewStore({ ...newStore, name: e.target.value })} 
                  placeholder="My Store"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="shop_domain">Shop Domain</Label>
                <Input 
                  id="shop_domain" 
                  value={newStore.shop_domain} 
                  onChange={(e) => setNewStore({ ...newStore, shop_domain: e.target.value })} 
                  placeholder="my-store.myshopify.com"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="admin_api_token">Admin API Token</Label>
                <Input 
                  id="admin_api_token" 
                  type="password"
                  value={newStore.admin_api_token} 
                  onChange={(e) => setNewStore({ ...newStore, admin_api_token: e.target.value })} 
                  placeholder="shpat_..."
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="api_version">API Version</Label>
                <Input 
                  id="api_version" 
                  value={newStore.api_version} 
                  onChange={(e) => setNewStore({ ...newStore, api_version: e.target.value })} 
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="webhook_secret">Webhook Secret (optional)</Label>
                <Input 
                  id="webhook_secret" 
                  type="password"
                  value={newStore.webhook_secret} 
                  onChange={(e) => setNewStore({ ...newStore, webhook_secret: e.target.value })} 
                />
              </div>
              <div className="flex items-center gap-2">
                <input 
                  type="checkbox" 
                  id="is_active" 
                  checked={newStore.is_active} 
                  onChange={(e) => setNewStore({ ...newStore, is_active: e.target.checked })} 
                />
                <Label htmlFor="is_active">Active</Label>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setShowCreateDialog(false)}>Cancel</Button>
              <Button onClick={createStore} disabled={creating || !newStore.name || !newStore.shop_domain}>
                {creating ? 'Creating...' : 'Create Store'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
      
      <div className="rounded-md border border-gray-200 dark:border-gray-700">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Domain</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>API Version</TableHead>
              <TableHead>Offers</TableHead>
              <TableHead>Users</TableHead>
              <TableHead className="w-24"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {stores.map((store) => (
              <TableRow key={store.id}>
                <TableCell>{store.id}</TableCell>
                <TableCell className="font-medium">
                  <div className="flex items-center gap-2">
                    <Store className="w-4 h-4 text-gray-400" />
                    {store.name}
                  </div>
                </TableCell>
                <TableCell className="text-gray-600 dark:text-gray-400 text-sm">
                  {store.shop_domain}
                </TableCell>
                <TableCell>
                  {store.is_active ? (
                    <Badge className="gap-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                      <Check className="w-3 h-3" />
                      Active
                    </Badge>
                  ) : (
                    <Badge variant="secondary" className="gap-1">
                      <X className="w-3 h-3" />
                      Inactive
                    </Badge>
                  )}
                </TableCell>
                <TableCell className="text-sm">{store.api_version}</TableCell>
                <TableCell>
                  <Badge variant="secondary">{store.offers_count}</Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="secondary">{store.users_count}</Badge>
                </TableCell>
                <TableCell>
                  <div className="flex items-center gap-1">
                    <Button variant="ghost" size="sm" asChild>
                      <a href={`/admin/stores/${store.id}`}>
                        <Edit className="w-4 h-4" />
                      </a>
                    </Button>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      onClick={() => deleteStore(store.id)}
                      className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                    >
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
            {stores.length === 0 && (
              <TableRow>
                <TableCell colSpan={8} className="text-center py-8 text-gray-500">
                  No stores yet. Click "Add Store" to create one.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
    </Container>
  );
}

const root = document.getElementById('admin-stores-root');
if (root) {
  createRoot(root).render(<AdminStoresPage />);
}
