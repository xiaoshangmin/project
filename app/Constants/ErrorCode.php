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
     * @Message("未知错误")
     */
    const UNKNOWN = 99999;
    /**
     * @Message("ok")
     */
    const SUCCESS = 1;
    /**
     * @Message("fail")
     */
    const FAIL = -1;
    /**
     * @Message("无效参数")
     */
    const INVALID_PARAM = 40000;
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
    /**
     * @Message("无法生成有效文件")
     */
    const NO_FILE_GOTENBERG = 40005;
}