<?php

namespace App\Services;

use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\City;

class AddressService
{
    protected KomerceService $komerceService;

    public function __construct(KomerceService $komerceService)
    {
        $this->komerceService = $komerceService;
    }

    public function createAddress(int $userId, array $data): UserAddress
    {
        // 1. Auto-Map Komerce ID via Service
        $data['komerce_destination_id'] = $this->resolveKomerceId(
            $data['district_code'], 
            $data['city_code']
        );

        $data['user_id'] = $userId;
        $data['is_active'] = true;

        return DB::transaction(function () use ($data, $userId) {
            // Jika user set sebagai default, matikan default yang lain
            if (!empty($data['is_default'])) {
                $this->resetDefaultAddress($userId);
            }
            return UserAddress::create($data);
        });
    }

    public function updateAddress(UserAddress $address, array $data): bool
    {
        // 1. Cek apakah lokasi berubah? Jika ya, cari ID Komerce baru
        if (isset($data['district_code']) || isset($data['city_code'])) {
            $distCode = $data['district_code'] ?? $address->district_code;
            $cityCode = $data['city_code'] ?? $address->city_code;
            
            $data['komerce_destination_id'] = $this->resolveKomerceId($distCode, $cityCode);
        }

        return DB::transaction(function () use ($address, $data) {
            if (!empty($data['is_default'])) {
                $this->resetDefaultAddress($address->user_id, $address->id);
            }
            return $address->update($data);
        });
    }

    public function deleteAddress(int $userId, int $addressId): bool
    {
        return (bool) UserAddress::where('user_id', $userId)
            ->where('id', $addressId)
            ->delete();
    }

    public function setAsDefault(int $userId, int $addressId): bool
    {
        $address = UserAddress::where('user_id', $userId)->find($addressId);
        
        if (!$address) return false;

        DB::transaction(function () use ($userId, $addressId) {
            $this->resetDefaultAddress($userId);
            UserAddress::where('id', $addressId)->update(['is_default' => true]);
        });

        return true;
    }

    private function resolveKomerceId($districtCode, $cityCode): ?int
    {
        $district = District::where('code', $districtCode)->first();
        $city = City::where('code', $cityCode)->first();

        if ($district && $city) {
            // Panggil Service Komerce (Cache Enabled)
            return $this->komerceService->findDestinationId($district->name, $city->name);
        }
        
        return null;
    }

    private function resetDefaultAddress(int $userId, ?int $exceptId = null): void
    {
        $query = UserAddress::where('user_id', $userId)->where('is_default', true);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_default' => false]);
    }
}