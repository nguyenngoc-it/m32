<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Carbon\Carbon;
use Gobiz\Event\EventService;
use Gobiz\Workflow\SubjectInterface;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Application\Model\Application;
use Modules\Location\Models\Location;
use Modules\Order\Events\PublicEvents\OrderChangeStatus;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

/**
 * Class Order
 *
 * @property int $id
 * @property int $application_id
 * @property int $shipping_partner_id
 * @property string $shipping_partner_code
 * @property string $shipping_carrier_code
 * @property string $ref
 * @property string $code
 * @property string $tracking_no
 * @property string $sorting_code
 * @property string $sorting_no
 * @property float $fee
 * @property float $cod
 * @property float total_amount
 * @property float order_amount
 * @property float $weight
 * @property float $length
 * @property float $width
 * @property float $height
 * @property float $volume
 * @property string $sender_name
 * @property string $sender_phone
 * @property string $sender_province_code
 * @property string $sender_district_code
 * @property string $sender_ward_code
 * @property string $sender_address
 * @property string $receiver_name
 * @property string $receiver_phone
 * @property string $receiver_province_code
 * @property string $receiver_district_code
 * @property string $receiver_ward_code
 * @property string $receiver_address
 * @property string receiver_postal_code
 * @property string $note
 * @property string $status
 * @property string $original_status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Application $application
 * @property ShippingPartner $shippingPartner
 * @property Collection|OrderItem[] $items
 * @property Location|null senderWard
 * @property Location|null senderDistrict
 * @property Location|null senderProvince
 * @property Location|null receiverWard
 * @property Location|null receiverDistrict
 * @property Location|null receiverProvince
 * @property-read string receiver_full_address
 * @property-read string sender_full_address
 */
class Order extends Model implements SubjectInterface
{
    protected $table = 'orders';

    protected $casts = [
        'paid_by_customer' => 'boolean'
    ];

    /*
     * Statuses
     */
    const STATUS_CREATING      = 'CREATING'; // Đang kết đơn
    const STATUS_READY_TO_PICK = 'READY_TO_PICK'; // Chờ đối tác lấy hàng
    const STATUS_PICKED_UP     = 'PICKED_UP'; // Đã đi lấy hàng
    const STATUS_DELIVERING    = 'DELIVERING'; // Đang giao
    const STATUS_DELIVERED     = 'DELIVERED'; // Đã giao
    const STATUS_RETURNING     = 'RETURNING'; // Đang trả hàng
    const STATUS_RETURNED      = 'RETURNED'; // Đã trả hàng
    const STATUS_ERROR         = 'ERROR'; // Lỗi trong quá trình giao
    const STATUS_CANCEL        = 'CANCEL'; // Đơn bị hủy

    /**
     * @return BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function shippingPartner()
    {
        return $this->belongsTo(ShippingPartner::class, 'shipping_partner_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * Set subject's place
     *
     * @return string
     */
    public function getSubjectPlace()
    {
        return $this->getAttribute('status');
    }

    /**
     * Get current subject's place
     *
     * @param string $place
     * @throws WorkflowException
     */
    public function setSubjectPlace($place)
    {
        if (!$this->update(['status' => $place])) {
            throw new WorkflowException("Update status {$place} for order {$this->getKey()} failed");
        }
    }

    /**
     * Thay đổi trạng thái đơn
     *
     * @param string $status
     * @param User $creator
     * @param array $payload
     * @throws WorkflowException
     */
    public function changeStatus($status, User $creator, array $payload = [])
    {
        Service::order()->workflow()->change($this, $status, array_merge($payload, ['creator' => $creator]));
        $order                  = $this->refresh();
        EventService::publicEventDispatcher()->publish(OrderEvent::M32_ORDER, new OrderChangeStatus($order));
    }

    /**
     * Thay đổi trạng thái đơn bỏ qua việc tuân theo workflow
     *
     * @param string $status
     */
    public function changeStatusWithoutFlow(string $status)
    {
        $this->status = $status;
        $this->save();
        EventService::publicEventDispatcher()->publish(OrderEvent::M32_ORDER, new OrderChangeStatus($this));
    }

    /**
     * Kiểm tra có thể đổi trạng thái đơn hay không
     *
     * @param string $status
     * @return bool
     */
    public function canChangeStatus($status)
    {
        return Service::order()->workflow()->canChange($this, $status);
    }

    /**
     * Publish webhook event
     *
     * @param string $event
     * @param array $payload
     * @return \Gobiz\Support\RestApiResponse|null
     * @throws \Gobiz\Support\RestApiException
     */
    public function publishWebhookEvent($event, array $payload = [])
    {
        /**
         * @var Application $app
         */
        $app = $this->getAttribute('application');

        return $app->webhook()->publishEvent($event, $payload, 'ORDER.' . $this->getKey());
    }

    /**
     * @return BelongsTo
     */
    public function senderWard()
    {
        return $this->belongsTo(Location::class, 'sender_ward_code', 'code');
    }

    /**
     * @return BelongsTo
     */
    public function senderDistrict()
    {
        return $this->belongsTo(Location::class, 'sender_district_code', 'code');
    }

    /**
     * @return BelongsTo
     */
    public function senderProvince()
    {
        return $this->belongsTo(Location::class, 'sender_province_code', 'code');
    }

    /**
     * @return BelongsTo
     */
    public function receiverWard()
    {
        return $this->belongsTo(Location::class, 'receiver_ward_code', 'code');
    }

    /**
     * @return BelongsTo
     */
    public function receiverDistrict()
    {
        return $this->belongsTo(Location::class, 'receiver_district_code', 'code');
    }

    /**
     * @return BelongsTo
     */
    public function receiverProvince()
    {
        return $this->belongsTo(Location::class, 'receiver_province_code', 'code');
    }

    /**
     * @return string
     */
    public function getSenderFullAddressAttribute()
    {
        $ward = $this->getAttribute('senderWard');
        $district = $this->getAttribute('senderDistrict');
        $province = $this->getAttribute('senderProvince');

        return implode(' ', [$this->getAttribute('sender_address'), $ward->label ?? '', $district->label ?? '', $province->label ?? '']);
    }

    /**
     * @return string
     */
    public function getReceiverFullAddressAttribute()
    {
        $ward = $this->getAttribute('receiverWard');
        $district = $this->getAttribute('receiverDistrict');
        $province = $this->getAttribute('receiverProvince');

        return implode(' ', [$this->getAttribute('receiver_address'), $ward->label ?? '', $district->label ?? '', $province->label ?? '']);
    }

    /**
     * @return string
     */
    public function getItems($attr)
    {
        $items = $this->getAttribute('items');

        $stringItems = "";

        if ($items) {
            foreach ($items as $key => $item) {
                $stringItems .= $item->quantity . 'x ' . $item->{$attr} . (($key == count($items) - 1) ? '' : ', ');
            }
        }

        return $stringItems;
    }
}
