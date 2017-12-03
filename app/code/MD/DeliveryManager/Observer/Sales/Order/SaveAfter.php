<?php

namespace MD\DeliveryManager\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer){

        $order = $observer->getEvent()->getOrder();
        $allOrderItems = $order->getAllVisibleItems();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productLoader = $objectManager->get('Magento\Catalog\Model\ProductFactory');
        $categoryLoader = $objectManager->create('Magento\Catalog\Model\Category');

        $mailsToSend = array();



        //var_dump($catInfo->getData());die;
        //var_dump($catInfo->getLevel());die;

        foreach ($allOrderItems as $orderItem){
            $categoryArray = array();
            $product = $productLoader->create()->load($orderItem->getProductId());
            $categoryIds = $product->getCategoryIds();
            foreach ($categoryIds as $catId){
                $category = $categoryLoader->load($catId);
                $categoryArray[] = array(
                    "id" => $catId,
                    "level" => $category->getLevel(),
                    "deliveryEmail" => $category->getDeliveryManagerEmail()
                );
            }

            $maxLevelElements = array($categoryArray[0]);

            for ($i = 1; $i < count($categoryArray); $i++){
                if($categoryArray[$i]['level'] > $maxLevelElements[0]['level']){

                    $maxLevelElements = array($categoryArray[$i]);
                }
                elseif($categoryArray[$i]['level'] == $maxLevelElements[0]['level']){
                    $maxLevelElements[] = $categoryArray[$i];
                }
            }

            $index = count($maxLevelElements) > 1
                ? rand(0, count($maxLevelElements) - 1)
                : 0;

            $email = $maxLevelElements[$index]['deliveryEmail'];
            $emailData = array(
                "orderNumber" => $order->getIncrementId(),
                "customerFirstName" => 'TEST',
                "sku" => $product->getSku(),
                "name" => $product->getName(),
                "qty" => $product->getQty(),
                "price" => $product->getPrice(),
                "shippingAddress" => $product->getShippingAddress(),
            );
            if(array_key_exists($email, $mailsToSend))
            {
                $mailsToSend[$email][] = $emailData;
            } else {
                $mailsToSend[$email] = array($emailData);
            }
        }


        foreach (array_keys($mailsToSend) as $mail){

            $customerFirstName = '';
            $orderNumber = '';
            $shippingAddress = '';
            $orderedItems = array();

            /* Here we prepare data for our email  */
            foreach ($mailsToSend[$mail] as $item) {
                $orderNumber = $item['orderNumber'];
                $customerFirstName = $item['customerFirstName'];
                $shippingAddress = $item['shippingAddress'];
                $orderedItems[] = array(
                    "sku" => $item['sku'],
                    "name" => $item['name'],
                    "qty" => $item['qty'],
                    "price" => $item['price'],
                );
            }

            /* Receiver Detail  */
            $receiverInfo = [
                'name' => $mail,
                'email' => $mail,
            ];


            /* Sender Detail  */
            $senderInfo = [
                'name' => 'Magento 2 Store',
                'email' => 'adm.farmaz@gmail.com',
            ];


            /* Assign values for your template variables  */
            $emailTempVariables = array();

            $emailTempVariables['orderNumber'] = $orderNumber;
            $emailTempVariables['shippingAddress'] = $shippingAddress;
            $emailTempVariables['customerFirstName'] = $customerFirstName;
            $emailTempVariables['items'] = $orderedItems;



            $objectManager->get('MD\DeliveryManager\Helper\Email')->mailSendMethod(
                $emailTempVariables,
                $senderInfo,
                $receiverInfo
            );
        }
        //var_dump($emailTempVariables);die;

    }
}