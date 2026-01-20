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
import { Store, ArrowRight, Plus, Edit, Trash2, Check, X } from 'lucide-react';

interface Shop {
  id: number;
  name: string;
  shop_domain: string;
  is_active: boolean;
  api_version: string;
  offers_count: number;
  users_count: number | null;
  access_level: 'read-only' | 'read-write';
}

function ShopsPage() {
  const [shops, setShops] = useState<Shop[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Admin state
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

  // Edit store state
  const [editingDialogOpen, setEditingDialogOpen] = useState(false);
  const [editing, setEditing] = useState(false);
  const [editingStore, setEditingStore] = useState<any | null>(null);

  const rootEl = document.getElementById('shops-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const isAdmin = rootEl?.dataset.isAdmin === 'true';

  const fetchShops = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops`);
      setShops(data);
    } catch (err) {
      setError('Failed to load shops');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  useEffect(() => {
    fetchShops();
  }, [fetchShops]);

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
      fetchShops();
    } catch (err) {
      console.error('Failed to create store:', err);
      alert('Failed to create store');
    } finally {
      setCreating(false);
    }
  };

  const openEditDialog = async (id: number) => {
    setEditingDialogOpen(true);
    setEditingStore(null);
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/stores/${id}`);
      setEditingStore({
        id: data.id,
        name: data.name || '',
        shop_domain: data.shop_domain || '',
        admin_api_token: data.admin_api_token || '',
        api_version: data.api_version || '2025-01',
        webhook_secret: data.webhook_secret || '',
        is_active: data.is_active ?? true,
        app_name: data.app_name || '',
        api_key: data.api_key || '',
        api_secret_key: data.api_secret_key || '',
      });
    } catch (err) {
      console.error('Failed to load store for editing:', err);
      alert('Failed to load store for editing');
      setEditingDialogOpen(false);
    }
  };

  const saveEditStore = async () => {
    if (!editingStore || !editingStore.id) return;
    setEditing(true);
    try {
      await fetchWrapper.put(`${apiBase}/admin/stores/${editingStore.id}`, {
        name: editingStore.name,
        shop_domain: editingStore.shop_domain,
        app_name: editingStore.app_name || null,
        admin_api_token: editingStore.admin_api_token || null,
        api_version: editingStore.api_version,
        api_key: editingStore.api_key || null,
        api_secret_key: editingStore.api_secret_key || null,
        webhook_version: editingStore.webhook_version || undefined,
        webhook_secret: editingStore.webhook_secret || null,
        is_active: editingStore.is_active,
      });
      setEditingDialogOpen(false);
      fetchShops();
    } catch (err: any) {
      console.error('Failed to save store:', err);
      alert(err?.error || 'Failed to save store');
    } finally {
      setEditing(false);
    }
  };

  const deleteStore = async (id: number) => {
    if (!confirm('Are you sure you want to delete this store? This cannot be undone.')) return;
    try {
      await fetchWrapper.delete(`${apiBase}/admin/stores/${id}`, {});
      fetchShops();
    } catch (err: any) {
      console.error('Failed to delete store:', err);
      alert(err?.error || 'Failed to delete store');
    }
  };

  if (loading) {
    return (
      <Container>
        <MainTitle>Shops</MainTitle>
        <div className="space-y-4">
          {[1, 2, 3, 4, 5].map((i) => (
            <Skeleton key={i} className="h-16 w-full" />
          ))}
        </div>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <MainTitle>Shops</MainTitle>
        <div className="text-red-600 dark:text-red-400">{error}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="flex items-center justify-between mb-6">
        <MainTitle>Shops</MainTitle>
        {isAdmin && (
          <>
          <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="w-4 h-4 mr-2" />
                Add Store
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-lg" onPointerDownOutside={(e) => e.preventDefault()}>
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
                    type="text"
                    autoComplete="off"
                    value={newStore.admin_api_token} 
                    onChange={(e) => setNewStore({ ...newStore, admin_api_token: e.target.value })} 
                    placeholder="shpat_..."
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="api_version">API Version</Label>
                  <select
                    id="api_version"
                    className="w-full rounded-md border px-3 py-2"
                    value={newStore.api_version}
                    onChange={(e) => setNewStore({ ...newStore, api_version: e.target.value })}
                  >
                    <option value="2025-01">2025-01</option>
                    <option value="2025-07">2025-07</option>
                    <option value="2026-01">2026-01</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="webhook_secret">Webhook Secret (optional)</Label>
                  <Input 
                    id="webhook_secret" 
                    type="text"
                    autoComplete="off"
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

          <Dialog open={editingDialogOpen} onOpenChange={setEditingDialogOpen}>
            <DialogContent className="max-w-lg" onPointerDownOutside={(e) => e.preventDefault()}>
              <DialogHeader>
                <DialogTitle>Edit Store</DialogTitle>
                <DialogDescription>Update store settings and API credentials.</DialogDescription>
              </DialogHeader>
              <div className="space-y-4 py-4">
                {editingStore ? (
                  <>
                    <div className="space-y-2">
                      <Label htmlFor="edit_name">Store Name</Label>
                      <Input
                        id="edit_name"
                        value={editingStore.name}
                        onChange={(e) => setEditingStore({ ...editingStore, name: e.target.value })}
                        placeholder="My Store"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="edit_shop_domain">Shop Domain</Label>
                      <Input
                        id="edit_shop_domain"
                        value={editingStore.shop_domain}
                        onChange={(e) => setEditingStore({ ...editingStore, shop_domain: e.target.value })}
                        placeholder="my-store.myshopify.com"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="edit_admin_api_token">Admin API Token</Label>
                        <Input
                          id="edit_admin_api_token"
                          type="text"
                          autoComplete="off"
                          value={editingStore.admin_api_token}
                          onChange={(e) => setEditingStore({ ...editingStore, admin_api_token: e.target.value })}
                          placeholder="shpat_..."
                        />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="edit_api_version">API Version</Label>
                      <select
                        id="edit_api_version"
                        className="w-full rounded-md border px-3 py-2"
                        value={editingStore.api_version}
                        onChange={(e) => setEditingStore({ ...editingStore, api_version: e.target.value })}
                      >
                        <option value="2025-01">2025-01</option>
                        <option value="2025-07">2025-07</option>
                        <option value="2026-01">2026-01</option>
                      </select>
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="edit_webhook_secret">Webhook Secret (optional)</Label>
                      <Input
                        id="edit_webhook_secret"
                        type="text"
                        autoComplete="off"
                        value={editingStore.webhook_secret}
                        onChange={(e) => setEditingStore({ ...editingStore, webhook_secret: e.target.value })}
                      />
                    </div>
                    <div className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        id="edit_is_active"
                        checked={!!editingStore.is_active}
                        onChange={(e) => setEditingStore({ ...editingStore, is_active: e.target.checked })}
                      />
                      <Label htmlFor="edit_is_active">Active</Label>
                    </div>
                  </>
                ) : (
                  <div>Loading...</div>
                )}
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setEditingDialogOpen(false)}>Cancel</Button>
                <Button onClick={saveEditStore} disabled={editing || !editingStore || !editingStore.name || !editingStore.shop_domain}>
                  {editing ? 'Saving...' : 'Save Changes'}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
          </>
        )}
      </div>
      
      {shops.length === 0 ? (
        <div className="text-center py-12 text-gray-600 dark:text-gray-400">
          <Store className="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p>No shops available. {isAdmin ? 'Click "Add Store" to create one.' : 'Contact an administrator to get access to a shop.'}</p>
        </div>
      ) : (
        <div className="rounded-md border border-gray-200 dark:border-gray-700">
          <Table>
            <TableHeader>
              <TableRow>
                {isAdmin && <TableHead className="w-12">ID</TableHead>}
                <TableHead>Name</TableHead>
                <TableHead>Domain</TableHead>
                {isAdmin && <TableHead>Status</TableHead>}
                <TableHead>Offers</TableHead>
                {isAdmin && <TableHead>Users</TableHead>}
                <TableHead>Access</TableHead>
                <TableHead className="w-24 text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {shops.map((shop) => (
                <TableRow key={shop.id}>
                  {isAdmin && <TableCell className="text-xs font-mono text-gray-500">{shop.id}</TableCell>}
                  <TableCell className="font-medium">
                    <a href={`/shop/${shop.id}/offers`} className="flex items-center gap-2 hover:underline decoration-primary underline-offset-4">
                      <Store className="w-4 h-4 text-gray-400" />
                      {shop.name}
                    </a>
                  </TableCell>
                  <TableCell className="text-gray-600 dark:text-gray-400 text-sm">
                    {shop.shop_domain}
                  </TableCell>
                  {isAdmin && (
                    <TableCell>
                      {shop.is_active ? (
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
                  )}
                  <TableCell>
                    <Badge variant="secondary">{shop.offers_count} offers</Badge>
                  </TableCell>
                  {isAdmin && (
                    <TableCell>
                      <Badge variant="outline">{shop.users_count} users</Badge>
                    </TableCell>
                  )}
                  <TableCell>
                    <Badge variant={shop.access_level === 'read-write' ? 'default' : 'outline'}>
                      {shop.access_level}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-1">
                      {isAdmin && (
                        <>
                          <Button variant="ghost" size="sm" onClick={() => openEditDialog(shop.id)} title="Edit Settings">
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button 
                            variant="ghost" 
                            size="sm" 
                            onClick={() => deleteStore(shop.id)}
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                            title="Delete Store"
                            disabled={shop.offers_count > 0}
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </>
                      )}
                      <Button variant="ghost" size="sm" asChild title="View Offers">
                        <a href={`/shop/${shop.id}/offers`}>
                          <ArrowRight className="w-4 h-4" />
                        </a>
                      </Button>
                    </div>
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

const root = document.getElementById('shops-root');
if (root) {
  createRoot(root).render(<ShopsPage />);
}