<?php
namespace App\Enums;

enum OrderStatus: int {
    case SEARCHING_FOR_DRIVER  = 1;
    case DRIVER_ASSIGNED =2;
    case PICK_LAUNDRY = 3;
    case DELIVER_LAUNDRY = 4;
    case CANCEL = 5;
    case COMPLETED = 6;
}

function getOrderMessage($orderStatus) {
    $statusMessage = "";
    if($orderStatus == OrderStatus::CANCEL->value) $statusMessage = "Your order is cancelled";
    elseif($orderStatus == OrderStatus::COMPLETED->value) $statusMessage = "Your order is completed";
    elseif($orderStatus == OrderStatus::SEARCHING_FOR_DRIVER->value) $statusMessage = "Your order is placed successfully";
    elseif($orderStatus == OrderStatus::DRIVER_ASSIGNED->value) $statusMessage = "A driver has assigned to your order";
    elseif($orderStatus == OrderStatus::PICK_LAUNDRY->value) $statusMessage = "Driver is coming to picking your laundry, be ready!";
    elseif($orderStatus == OrderStatus::DELIVER_LAUNDRY->value) $statusMessage = "Driver is coming to deliver your laundry, be ready!";
    return $statusMessage;
}