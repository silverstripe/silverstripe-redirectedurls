<?php

SilverStripe\Control\RequestHandler::add_extension("RedirectedURLHandler");
SilverStripe\CMS\Controllers\ContentController::add_extension("RedirectedURLHandler");
SilverStripe\CMS\Controllers\ModelAsController::add_extension("RedirectedURLHandler");