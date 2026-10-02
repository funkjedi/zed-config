<?php

namespace Ds\Domain\Spur\Services;

use Ds\Domain\Spur\Enums\AssessmentService;
use Illuminate\Support\Facades\DB;
use Throwable;

class SpurThreatService
{
    public function getAssessmentFromSpurThreat(string $ip): ?array
    {
        try {
            $row = DB::connection('sys-backend')
                ->table('spur_threats')
                ->where('ip', $ip)
                ->firstOrFail();
        } catch (Throwable $e) {
            // intentionally catching everything and not just model not found.
            // if sys-backend database in down for maintenance, etc that don't
            // impact allowing contributions to proceed
            return null;
        }

        return $this->mapToAssessmentData($row);
    }

    private function mapToAssessmentData(object $row): array
    {
        $proxies = json_decode($row->proxies ?? '[]', true) ?: [];
        $risks = json_decode($row->risks ?? '[]', true) ?: [];

        $service = collect($proxies)
            ->first(fn (string $name) => AssessmentService::tryFrom($name) !== null);

        $hasProxy = ! empty($proxies);
        $hasVpn = collect($proxies)->contains(fn (string $p) => str_ends_with($p, '_VPN'));

        return [
            'source' => 'spur_threats',
            'ip' => $row->ip,
            'cc' => $row->ip_country,
            'ts' => now()->toIso8601String(),
            'service' => $service,
            'proxied' => $hasProxy,
            'vpn' => $hasVpn,
            'anon' => $row->threat_category === 'residential_proxy' || in_array('CALLBACK_PROXY', $risks),
            'dch' => str_contains($row->threat_category ?? '', 'data_center'),
            'rdp' => false,
        ];
    }
}
