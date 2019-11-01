<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Block\System\Config\Form;

use \Magento\Config\Block\System\Config\Form\Field;
use \Magento\Framework\Data\Form\Element\AbstractElement;

class CheckEnvironmentVariables extends Field
{
    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        $accessKeyId = getenv('AWS_ACCESS_KEY_ID');
        $secretAccessKey = getenv('AWS_SECRET_ACCESS_KEY');

        ob_start();
        ?>
        <td class="label"></td>
        <td class="value">
        <?php if ($accessKeyId === false): ?>
            <div style="color: #e22626;" >WARNING: environment variable AWS_ACCESS_KEY_ID not set.</div>
        <?php else: ?>
            <div style="color: #185b00;">OK: environment variable AWS_ACCESS_KEY_ID set as "<?=$accessKeyId ?>".</div>
        <?php endif ?>
        <?php if ($secretAccessKey === false): ?>
            <div style="color: #e22626;">WARNING: environment variable AWS_SECRET_ACCESS_KEY not set.</div>
        <?php else: ?>
            <div style="color: #185b00;">OK: environment variable AWS_SECRET_ACCESS_KEY set.</div>
        <?php endif ?>
        </td>
        <?php
        $html = ob_get_clean();

        return $this->_decorateRowHtml($element, $html);
    }
}
