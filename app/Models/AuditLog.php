<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'route_name',
        'controller_action',
        'request_data',
        'response_status',
        'response_size',
        'execution_time',
        'memory_usage',
        'session_id',
        'referer',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_data' => 'array',
        'execution_time' => 'decimal:2',
        'memory_usage' => 'integer',
        'response_size' => 'integer',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by IP address.
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to filter by method.
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope to filter by response status.
     */
    public function scopeByStatus($query, int $status)
    {
        return $query->where('response_status', $status);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope to filter slow requests.
     */
    public function scopeSlowRequests($query, float $threshold = 1000.0)
    {
        return $query->where('execution_time', '>=', $threshold);
    }

    /**
     * Scope to filter high memory usage.
     */
    public function scopeHighMemoryUsage($query, int $threshold = 50 * 1024 * 1024)
    {
        return $query->where('memory_usage', '>=', $threshold);
    }

    /**
     * Scope to filter failed requests.
     */
    public function scopeFailedRequests($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if ($this->execution_time < 1000) {
            return number_format($this->execution_time, 2) . 'ms';
        } else {
            return number_format($this->execution_time / 1000, 2) . 's';
        }
    }

    /**
     * Get formatted memory usage.
     */
    public function getFormattedMemoryUsageAttribute(): string
    {
        $bytes = $this->memory_usage;
        
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        } elseif ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Get formatted response size.
     */
    public function getFormattedResponseSizeAttribute(): string
    {
        $bytes = $this->response_size;
        
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Get status color class.
     */
    public function getStatusColorAttribute(): string
    {
        $status = $this->response_status;
        
        if ($status >= 500) {
            return 'text-red-600';
        } elseif ($status >= 400) {
            return 'text-yellow-600';
        } elseif ($status >= 300) {
            return 'text-blue-600';
        } else {
            return 'text-green-600';
        }
    }

    /**
     * Check if request was slow.
     */
    public function isSlowRequest(float $threshold = 1000.0): bool
    {
        return $this->execution_time >= $threshold;
    }

    /**
     * Check if request used high memory.
     */
    public function isHighMemoryUsage(int $threshold = 50 * 1024 * 1024): bool
    {
        return $this->memory_usage >= $threshold;
    }

    /**
     * Check if request failed.
     */
    public function isFailed(): bool
    {
        return $this->response_status >= 400;
    }
}

