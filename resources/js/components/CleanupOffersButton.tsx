import React, { useState, useEffect, useCallback } from 'react';
import { Button } from './ui/button';
import { Archive, Loader2 } from 'lucide-react';
import { fetchWrapper } from '@/fetchWrapper';

interface CleanupOffersButtonProps {
  shopId: string;
  apiBase: string;
  onCleanupSuccess: () => void;
}

export const CleanupOffersButton: React.FC<CleanupOffersButtonProps> = ({ 
  shopId, 
  apiBase, 
  onCleanupSuccess 
}) => {
  const [count, setCount] = useState<number>(0);
  const [loading, setLoading] = useState<boolean>(false);
  const [checking, setChecking] = useState<boolean>(true);

  const fetchCount = useCallback(async () => {
    try {
      const result = await fetchWrapper.get(`${apiBase}/shops/${shopId}/offers/cleanup-count`);
      setCount(result.count);
    } catch (err) {
      console.error('Failed to fetch cleanup count', err);
    } finally {
      setChecking(false);
    }
  }, [apiBase, shopId]);

  useEffect(() => {
    fetchCount();
  }, [fetchCount]);

  const handleCleanup = async () => {
    if (!confirm(`Are you sure you want to archive ${count} offers that ended more than 30 days ago?`)) {
      return;
    }

    setLoading(true);
    try {
      await fetchWrapper.post(`${apiBase}/shops/${shopId}/offers/cleanup`, {});
      setCount(0);
      onCleanupSuccess();
    } catch (err: any) {
      alert(err?.error || 'Failed to cleanup offers');
    } finally {
      setLoading(false);
    }
  };

  if (checking || count === 0) {
    return null;
  }

  return (
    <Button 
      variant="secondary" 
      onClick={handleCleanup} 
      disabled={loading}
      className="ml-2"
    >
      {loading ? (
        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
      ) : (
        <Archive className="w-4 h-4 mr-2" />
      )}
      Archive {count} ended offers &gt;30d
    </Button>
  );
};
