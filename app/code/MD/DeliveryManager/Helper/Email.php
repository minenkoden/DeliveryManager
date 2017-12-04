<?php
/**
 * Created by PhpStorm.
 * User: minenko
 * Date: 03.12.17
 * Time: 19:46
 */

namespace MD\DeliveryManager\Helper;

use Magento\Framework\App\RequestInterface;


class Email extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
        , \Magento\Framework\App\Request\Http $request
        , \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
        , \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_request = $request;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * [sendInvoicedOrderEmail description]
     * @param  Mixed $emailTemplateVariables
     * @param  Mixed $senderInfo
     * @param  Mixed $receiverInfo
     * @return void
     */
    public function mailSendMethod($emailTemplateVariables,$senderInfo,$receiverInfo)
    {
        $itemHtml = '';
        $totals = 0;
        foreach ($emailTemplateVariables['items'] as $item){
            $itemHtml .= '<tr>';
            $itemHtml .= '<th class="item">' . $item['sku'] . '</th>';
            $itemHtml .= '<th class="item">' . $item['name'] . '</th>';
            $itemHtml .= '<th class="item">' . $item['qty'] . '</th>';
            $itemHtml .= '<th class="item">' . $item['price']*$item['qty'] . '</th>';
            $itemHtml .= '</tr>';
            $totals += $item['price']*$item['qty'];
        }

        $store = $this->_storeManager->getStore()->getId();
        $transport = $this->_transportBuilder->setTemplateIdentifier('manager_delivery_mail')
            ->setTemplateOptions(['area' => 'adminhtml', 'store' => $store])
            ->setTemplateVars(
                [
                    'store' => $this->_storeManager->getStore(),
                    'orderNumber' => $emailTemplateVariables['orderNumber'],
                    'customerFirstName' => $emailTemplateVariables['customerFirstName'],
                    //'billingAddress' => $emailTemplateVariables['billingAddress'],
                    'shippingAddress' => $emailTemplateVariables['shippingAddress'],
                    'items' => $itemHtml,
                    'totals'=> 'TOTAL: ' . $totals
                ]
            )
            ->setFrom('general')
            ->addTo($receiverInfo['email'], $receiverInfo['email'])
            ->getTransport();
        $transport->sendMessage();
    }
}