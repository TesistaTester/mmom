<?php
namespace App\Modules\Identity;

use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use App\Models\MmomUser;

final class MmomAuthorization
{
    public function requireRole(MmomUser $user, string $role): void
    {
        if (!$user->is_active || !DB::table('identity.user_roles')->where('user_id',$user->id)->where('role',$role)->exists()) {
            throw new AuthorizationException('No autorizado para esta función.');
        }
    }
    public function requireAdmin(MmomUser $user): void
    {
        $this->requireRole($user,'admin');
        // In E2, admin operations FAIL CLOSED until Fortify 2FA is integrated and confirmed.
        if (!$user->two_factor_confirmed_at) {
            throw new AuthorizationException('El administrador debe completar MFA confirmado.');
        }
    }
    public function requireAssignedCompany(MmomUser $user, string $gameId): string
    {
        $this->requireRole($user,'player');
        $company = DB::table('game.assignments')->where('game_id',$gameId)
          ->where('user_id',$user->id)->where('is_current',true)->value('company_id');
        if (!$company) throw new AuthorizationException('No existe una empresa asignada en esta partida.');
        return (string) $company;
    }
}
