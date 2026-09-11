<?php

return array_merge(
    include __DIR__ . "/conf/schedule_fork_conf.php",
    include __DIR__ . "/conf/schedule_url_conf.php",
    include __DIR__ . "/conf/schedule_k8s_conf.php",
);
