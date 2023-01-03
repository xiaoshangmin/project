<?php

declare (strict_types=1);
namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

#[Constants]
class ErrorCode extends AbstractConstants
{
    /**
     * @Message("Server Error！")
     */
    const SERVER_ERROR = 500;
    /**
     * @Message("ok")
     */
    const SUCCESS = 1;
    /**
     * @Message("fail")
     */
    const FAIL = -1;
    /**
     * @Message("上传文件失败")
     */
    const UPLOAD_FAIL = 40001;
    /**
     * @Message("请上传pdf文件")
     */
    const PLEASE_UPDATE_PDF = 40002;
    /**
     * @Message("上传的文件太大了")
     */
    const OVER_MAX_SIZE = 40003;
    /**
     * @Message("请上传word文件")
     */
    const PLEASE_UPDATE_WORD = 40004;
}