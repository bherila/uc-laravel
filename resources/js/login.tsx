import './bootstrap';
import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import axios from 'axios';

function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  // Password Reset State
  const [showResetDialog, setShowResetDialog] = useState(false);
  const [resetEmail, setResetEmail] = useState('');
  const [resetCode, setResetCode] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConfirmation, setNewPasswordConfirmation] = useState('');
  const [resetStep, setResetStep] = useState(1); // 1: enter email, 2: enter code+new password
  const [resetError, setResetError] = useState<string | null>(null);
  const [resetLoading, setResetLoading] = useState(false);
  const [resetSuccess, setResetSuccess] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const response = await axios.post('/login', {
        email,
        password,
        remember,
      });

      if (response.data.redirect) {
        window.location.href = response.data.redirect;
      }
    } catch (err: any) {
      if (err.response?.status === 422) {
        setError(err.response.data.errors.email?.[0] || 'Invalid credentials');
      } else {
        setError('An unexpected error occurred. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = () => {
    setShowResetDialog(true);
    setResetStep(1);
    setResetEmail('');
    setResetCode('');
    setNewPassword('');
    setNewPasswordConfirmation('');
    setResetError(null);
    setResetSuccess(null);
  };

  const handleSendResetCode = async (e: React.FormEvent) => {
    e.preventDefault();
    setResetLoading(true);
    setResetError(null);
    setResetSuccess(null);

    try {
      const response = await axios.post('/api/forgot-password', { email: resetEmail });
      setResetSuccess(response.data.message || 'Password reset code sent!');
      setResetStep(2);
    } catch (err: any) {
      setResetError(err.response?.data?.message || 'Failed to send reset code. Please try again.');
      setResetError(err.response?.data?.errors?.email?.[0] || 'Failed to send reset code. Please try again.');
    } finally {
      setResetLoading(false);
    }
  };

  const handleResetPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setResetLoading(true);
    setResetError(null);
    setResetSuccess(null);

    try {
      const response = await axios.post('/api/reset-password', {
        token: resetCode,
        email: resetEmail,
        password: newPassword,
        password_confirmation: newPasswordConfirmation,
      });

      setResetSuccess(response.data.message || 'Password has been reset successfully. You are now logged in.');
      // Optionally log in the user or redirect
      window.location.href = '/shops'; // Assuming /shops is a common landing page after login
    } catch (err: any) {
      setResetError(err.response?.data?.message || 'Failed to reset password. Please check your code and try again.');
      if (err.response?.data?.errors) {
        if (err.response.data.errors.email) setResetError(err.response.data.errors.email[0]);
        if (err.response.data.errors.password) setResetError(err.response.data.errors.password[0]);
        if (err.response.data.errors.token) setResetError(err.response.data.errors.token[0]);
      }
    } finally {
      setResetLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <Card className="w-full max-w-md">
        <CardHeader className="space-y-1">
          <CardTitle className="text-2xl font-bold text-center">Sign in</CardTitle>
          <CardDescription className="text-center">
            Enter your email and password to access your account
          </CardDescription>
        </CardHeader>
        <form onSubmit={handleSubmit}>
          <CardContent className="space-y-4">
            {error && (
              <Alert variant="destructive">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                placeholder="name@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label htmlFor="password">Password</Label>
                <Button variant="link" size="sm" type="button" onClick={handleForgotPassword}>
                  Forgot your password?
                </Button>
              </div>
              <Input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>
            <div className="flex items-center space-x-2">
              <Checkbox
                id="remember"
                checked={remember}
                onCheckedChange={(checked) => setRemember(checked === true)}
              />
              <Label
                htmlFor="remember"
                className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              >
                Remember me
              </Label>
            </div>
          </CardContent>
          <CardFooter>
            <Button className="w-full" type="submit" disabled={loading}>
              {loading ? <Spinner size="small" className="mr-2" /> : null}
              Sign In
            </Button>
          </CardFooter>
        </form>
      </Card>

      {/* Password Reset Dialog */}
      <Dialog open={showResetDialog} onOpenChange={setShowResetDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{resetStep === 1 ? 'Forgot Password' : 'Reset Password'}</DialogTitle>
          </DialogHeader>
          {resetError && (
            <Alert variant="destructive">
              <AlertDescription>{resetError}</AlertDescription>
            </Alert>
          )}
          {resetSuccess && resetStep === 1 && (
            <Alert>
              <AlertDescription>{resetSuccess}</AlertDescription>
            </Alert>
          )}
          {resetStep === 1 && (
            <form onSubmit={handleSendResetCode} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="reset-email">Email</Label>
                <Input
                  id="reset-email"
                  type="email"
                  value={resetEmail}
                  onChange={(e) => setResetEmail(e.target.value)}
                  required
                />
              </div>
              <DialogFooter>
                <Button type="submit" disabled={resetLoading}>
                  {resetLoading ? <Spinner size="small" className="mr-2" /> : null}
                  Send Reset Code
                </Button>
              </DialogFooter>
            </form>
          )}
          {resetStep === 2 && (
            <form onSubmit={handleResetPassword} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="reset-code">Reset Code</Label>
                <Input
                  id="reset-code"
                  type="text"
                  value={resetCode}
                  onChange={(e) => setResetCode(e.target.value)}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="new-password">New Password</Label>
                <Input
                  id="new-password"
                  type="password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="new-password-confirmation">Confirm New Password</Label>
                <Input
                  id="new-password-confirmation"
                  type="password"
                  value={newPasswordConfirmation}
                  onChange={(e) => setNewPasswordConfirmation(e.target.value)}
                  required
                />
              </div>
              <DialogFooter>
                <Button type="submit" disabled={resetLoading}>
                  {resetLoading ? <Spinner size="small" className="mr-2" /> : null}
                  Reset Password
                </Button>
              </DialogFooter>
            </form>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}

const el = document.getElementById('login-root');
if (el) {
  createRoot(el).render(<Login />);
}