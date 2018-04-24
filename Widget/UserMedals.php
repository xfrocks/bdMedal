<?php

namespace Xfrocks\Medal\Widget;

use XF\Widget\AbstractWidget;
use Xfrocks\Medal\XF\Entity\User;

class UserMedals extends AbstractWidget
{
    public function render()
    {
        if (empty($this->contextParams['user'])) {
            return '';
        }

        /** @var User $userRef */
        $userRef =& $this->contextParams['user'];
        if ($userRef->medal_count === 0) {
            return '';
        }

        $viewParams = [
            'user' => $this->contextParams['user']
        ];

        return $this->renderer('bdmedal_widget_user_medals', $viewParams);
    }
}
