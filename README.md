# worker

1. start conf
    * registry info
        * redis ip, port
1. redis schema
    * key
        * runtime_conf - runtime_conf_t
        * worker_id_set - set<worker_id_t>
        * worker_status_{worker_id} - worker_status_t
1. typedef
    * runtime_conf_t
        * .
    * worker_id_t
        * string : guid.. hash?
    * worker_status_t
        * worker_id : worker_id_t
        * worker_host : string
        * worker_pid : int
        * worker_runtime_conf : runtime_conf_t
        * worker_start : timestamp_t
        * worker_control_request_queue : string
        * worker_task_request_queue : string
        * current_task : task_t
        * current_task_start : timestamp_t
        * current_task_status : task_status_t
        * last_task : task_t
        * last_task_result : task_result_t
        * last_task_begin : timestamp_t
        * last_task_end : timestamp_t
        * status_update : timestamp_t
    * timestamp_t
        * string : 'yyyy-mm-dd hh-MM:ss.dddddd KST'
    * task_t
        * task_id : task_id_t : string
        * task_kind : task_kind_t : enum
            * php_closure
            * dump_table_task
        * task_param
            * (depends on test_kind)
        * maxdop_key
    * task_status_t
        * .
    * task_result_t
        * .

# task emitter

1. find available worker(s)
1. submit task(s)
1. wait result(s)


