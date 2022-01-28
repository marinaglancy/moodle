<?php

namespace format_topics\output\courseformat\content\section;

class cmitem extends \core_courseformat\output\local\content\section\cmitem {

    public function get_template_name(): string {
        return 'format_topics/local/content/section/cmitem';
    }
}
