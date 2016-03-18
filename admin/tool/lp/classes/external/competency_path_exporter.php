<?php
namespace tool_lp\external;
defined('MOODLE_INTERNAL') || die();

use renderer_base;
use stdClass;
use tool_lp\competency;

class competency_path_exporter extends exporter {

    /**
     * Constructor.
     *
     * @param array $related - related objects.
     */
    public function __construct($related) {
        parent::__construct([], $related);
    }

    protected static function define_related() {
        return [
            'ancestors' => 'tool_lp\\competency[]',
            'framework' => 'tool_lp\\competency_framework',
            'context' => 'context'
        ];
    }

    protected static function define_other_properties() {
        return [
            'ancestors' => [
                'type' => path_node_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'framework' => [
                'type' => path_node_exporter::read_properties_definition()
            ]
        ];
    }

    protected function get_other_values(renderer_base $output) {
        $result = new stdClass();
        $ancestors = [];
        $nodescount = count($this->related['ancestors']);
        $i = 1;
        foreach ($this->related['ancestors'] as $competency) {
            $exporter = new path_node_exporter([
                    'id' => $competency->get_id(),
                    'name' => $competency->get_idnumber(),
                    'position' => $i,
                    'first' => $i == 1,
                    'last' => $i == $nodescount
                ], [
                    'context' => $this->related['context'],
                ]
            );
            $ancestors[] = $exporter->export($output);
            $i++;
        }
        $result->ancestors = $ancestors;

        $exporter = new path_node_exporter([
                'id' => $this->related['framework']->get_id(),
                'name' => $this->related['framework']->get_shortname(),
                'first' => 0,
                'last' => 0,
                'position' => -1
            ], [
                'context' => $this->related['context']
            ]
        );
        $result->framework = $exporter->export($output);

        return (array) $result;
    }
}
