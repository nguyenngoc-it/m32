<?php

namespace Modules\Application\Model;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Application\Model\Application;
use Modules\User\Models\User;

/**
 * Class ApplicationMember
 *
 * @property int $id
 * @property int $application_id
 * @property int $user_id
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Application $project
 * @property-read User $user
 */
class ApplicationMember extends Model
{
    protected $table = 'application_members';

    /**
     * @return BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
