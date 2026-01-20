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
import { Plus, Edit, Trash2, Shield } from 'lucide-react';

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

function AdminUsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [creating, setCreating] = useState(false);
  const [newUser, setNewUser] = useState({ email: '', alias: '', password: '', is_admin: false });
  
  const apiBase = document.getElementById('admin-users-root')?.dataset.apiBase || '/api';

  const fetchUsers = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/users`);
      setUsers(data);
    } catch (err) {
      setError('Failed to load users');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  const createUser = async () => {
    setCreating(true);
    try {
      await fetchWrapper.post(`${apiBase}/admin/users`, newUser);
      setShowCreateDialog(false);
      setNewUser({ email: '', alias: '', password: '', is_admin: false });
      fetchUsers();
    } catch (err) {
      console.error('Failed to create user:', err);
      alert('Failed to create user');
    } finally {
      setCreating(false);
    }
  };

  const deleteUser = async (id: number) => {
    if (!confirm('Are you sure you want to delete this user?')) return;
    try {
      await fetchWrapper.delete(`${apiBase}/admin/users/${id}`, {});
      fetchUsers();
    } catch (err) {
      console.error('Failed to delete user:', err);
      alert('Failed to delete user');
    }
  };

  if (loading) {
    return (
      <Container>
        <MainTitle>Admin: Users</MainTitle>
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
        <MainTitle>Admin: Users</MainTitle>
        <div className="text-red-600 dark:text-red-400">{error}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="flex items-center justify-between mb-6">
        <MainTitle>Admin: Users</MainTitle>
        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="w-4 h-4 mr-2" />
              Add User
            </Button>
          </DialogTrigger>
          <DialogContent onPointerDownOutside={(e) => e.preventDefault()}>
            <DialogHeader>
              <DialogTitle>Create New User</DialogTitle>
              <DialogDescription>Add a new user to the system.</DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input 
                  id="email" 
                  type="email" 
                  value={newUser.email} 
                  onChange={(e) => setNewUser({ ...newUser, email: e.target.value })} 
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="alias">Alias (optional)</Label>
                <Input 
                  id="alias" 
                  value={newUser.alias} 
                  onChange={(e) => setNewUser({ ...newUser, alias: e.target.value })} 
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input 
                  id="password" 
                  type="password" 
                  value={newUser.password} 
                  onChange={(e) => setNewUser({ ...newUser, password: e.target.value })} 
                />
              </div>
              <div className="flex items-center gap-2">
                <input 
                  type="checkbox" 
                  id="is_admin" 
                  checked={newUser.is_admin} 
                  onChange={(e) => setNewUser({ ...newUser, is_admin: e.target.checked })} 
                />
                <Label htmlFor="is_admin">Admin</Label>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setShowCreateDialog(false)}>Cancel</Button>
              <Button onClick={createUser} disabled={creating || !newUser.email || !newUser.password}>
                {creating ? 'Creating...' : 'Create User'}
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
              <TableHead>Email</TableHead>
              <TableHead>Alias</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Shop Access</TableHead>
              <TableHead>Last Login</TableHead>
              <TableHead className="w-24"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {users.map((user) => (
              <TableRow key={user.id}>
                <TableCell>{user.id}</TableCell>
                <TableCell className="font-medium">{user.email}</TableCell>
                <TableCell className="text-gray-600 dark:text-gray-400">
                  {user.alias || '-'}
                </TableCell>
                <TableCell>
                  {!!user.is_admin || user.id === 1 ? (
                    <Badge className="gap-1">
                      <Shield className="w-3 h-3" />
                      Admin
                    </Badge>
                  ) : (
                    <Badge variant="outline">User</Badge>
                  )}
                </TableCell>
                <TableCell>
                  {user.shop_accesses.length > 0 ? (
                    <div className="flex flex-wrap gap-1">
                      {user.shop_accesses.slice(0, 2).map((access) => (
                        <Badge key={access.id} variant="secondary" className="text-xs">
                          {access.shop?.name || `Shop ${access.shopify_shop_id}`}
                        </Badge>
                      ))}
                      {user.shop_accesses.length > 2 && (
                        <Badge variant="secondary" className="text-xs">
                          +{user.shop_accesses.length - 2} more
                        </Badge>
                      )}
                    </div>
                  ) : (
                    <span className="text-gray-400 text-sm">None</span>
                  )}
                </TableCell>
                <TableCell className="text-sm text-gray-600 dark:text-gray-400">
                  {user.last_login_at 
                    ? formatDistanceToNow(parseISO(user.last_login_at), { addSuffix: true })
                    : 'Never'
                  }
                </TableCell>
                <TableCell>
                  <div className="flex items-center gap-1">
                    <Button variant="ghost" size="sm" asChild>
                      <a href={`/admin/users/${user.id}`}>
                        <Edit className="w-4 h-4" />
                      </a>
                    </Button>
                    {user.id !== 1 && (
                      <Button 
                        variant="ghost" 
                        size="sm" 
                        onClick={() => deleteUser(user.id)}
                        className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </Container>
  );
}

const root = document.getElementById('admin-users-root');
if (root) {
  createRoot(root).render(<AdminUsersPage />);
}
