<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PurchaseOrderItems;
use App\ProductionInventory;
use Validator;

class ProductionController extends Controller
{
    public function getPurchaseOrderItems()
    {
        $items = PurchaseOrderItems::all();
        $itemsArray = [];
        foreach ($items as $item) {
            array_push($itemsArray, [
                'id' => $item->id,
                'code' => $item->poi_code,
                'partNumber' => $item->poi_partnum,
                'itemDescription' => $item->poi_itemdescription,
                'customerName' => $item->po->customer->companyname,
            ]);
        }
        return response()->json([
            'purchaseOrderItems' => $itemsArray
        ]);
    }

    public function addInventoryItem(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'customerName' => 'string'
        // ]);

        $item = new ProductionInventory;
        $item->fw_customer = $request->customerName;
        $item->fw_code = $request->code;
        $item->fw_partnumber = $request->partNumber;
        $item->fw_itemdescription = $request->itemDescription;
        $item->fw_wipquantity = $request->wipQuantity;
        $item->fw_fgquantity = $request->fgQuantity;
        $item->fw_wiplocation = $request->wipLocation;
        $item->fw_fglocation = $request->fgLocation;
        $item->fw_wipremarks = $request->wipRemarks;
        $item->fw_fgremarks = $request->fgRemarks;
        $item->fw_wipmin = $request->wipMin;
        $item->fw_wipmax = $request->wipMax;
        $item->save();

        return response()->json([
            'newInventoryItem' => $item,
            'message' => 'New inventory item has been added.'
        ]);
    }

    private function display($item)
    {
        return [
            'id' => $item->id,
            'fw_customer' => $item->customerName,
            'fw_code' => $item->code,
            'fw_partnumber' => $item->partnumber,
            'fw_itemdescription' => $item->itemDescription,
            'fw_wipquantity' => $item->wipQuantity,
            'fw_fgquantity' => $item->fgQuantity,
            'fw_wiplocation' => $item->wipLocation,
            'fw_fglocation' => $item->fgLocation,
            'fw_wipremarks' => $item->wipRemarks,
            'fw_fgremarks' => $item->fgRemarks,
            'fw_wipmin' => $item->wipMin,
            'fw_wipmax' => $item->wipMax,
        ];
    }
}
