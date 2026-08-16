<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PushSubscriptionService
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array{subscription: PushSubscription, created: bool}
     */
    public function register(string $token, ?User $user, array $metadata = []): array
    {
        $tokenHash = hash('sha256', $token);

        try {
            return $this->persist($token, $tokenHash, $user, $metadata);
        } catch (UniqueConstraintViolationException) {
            return $this->persist($token, $tokenHash, $user, $metadata);
        }
    }

    public function unsubscribe(string $token): bool
    {
        return PushSubscription::query()
            ->where('token_hash', hash('sha256', $token))
            ->update([
                'is_active' => false,
                'permission_status' => 'default',
                'unsubscribed_at' => now(),
            ]) > 0;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{subscription: PushSubscription, created: bool}
     */
    private function persist(string $token, string $tokenHash, ?User $user, array $metadata): array
    {
        return DB::transaction(function () use ($token, $tokenHash, $user, $metadata): array {
            $subscription = PushSubscription::query()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            $created = $subscription === null;
            $subscription ??= new PushSubscription;

            $previousDeviceSubscription = null;
            if (filled($metadata['device_uuid'] ?? null)) {
                $previousDeviceSubscription = PushSubscription::query()
                    ->where('device_uuid', $metadata['device_uuid'])
                    ->where('token_hash', '!=', $tokenHash)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                PushSubscription::query()
                    ->where('device_uuid', $metadata['device_uuid'])
                    ->where('token_hash', '!=', $tokenHash)
                    ->active()
                    ->update([
                        'is_active' => false,
                        'permission_status' => 'default',
                        'unsubscribed_at' => now(),
                    ]);
            }

            $subscription->fill([
                'token' => $token,
                'token_hash' => $tokenHash,
                ...$metadata,
                'permission_status' => 'granted',
                'is_active' => true,
                'last_seen_at' => now(),
                'last_registered_at' => now(),
                'unsubscribed_at' => null,
            ]);

            if ($user !== null) {
                $subscription->user()->associate($user);
            }

            $subscription->save();

            if ($created && $previousDeviceSubscription !== null) {
                $subscription->topics()->sync($previousDeviceSubscription->topics()->pluck('push_topics.id')->all());
                $subscription->forceFill([
                    'preferences_configured_at' => $previousDeviceSubscription->preferences_configured_at,
                ])->save();
            }

            return ['subscription' => $subscription, 'created' => $created];
        });
    }
}
