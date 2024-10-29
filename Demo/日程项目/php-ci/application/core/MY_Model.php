<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 公共Model 
 * @auther bruce.d
 * @version
 */
class MY_Model extends CI_Model
{
	protected $master ;
	protected $slave ;
	public $table ;
    private static $dbconnection;
	
	/**
	 * 加载读写数据库
	 * @auther bruce.d
	 * @version
	 */
	function __construct()
	{
		parent::__construct() ;

//		$this->db = $this->slave = $this->master = $this->load->database('default' , TRUE);
        $this->db = $this->slave = $this->master =self::getInstance();
	}

    /***
     * @return 获取数据库实例
     */
    static function getInstance(){
        $CI =& get_instance();
        if(self::$dbconnection){
            return self::$dbconnection;
        }else{
            self::$dbconnection = $CI->load->database('default' , TRUE);
            return self::$dbconnection;
        }
    }

	public function save( $data )
	{
		if( empty( $data ['id']))
		{
			$id = $this->insertData( $data , TRUE );
		}
		else
		{
			$this->updateData( $data , array('id' => $data['id'] ) );
			$id = $data['id'];
		}
		return $id;
	}

	/*
	 * 获取表单总数
	 */
    public function getCount($where = array(),$group = ''){

        $this->like($where);
        if (is_array($where)){
            foreach ($where as $key=>$value){
                if (is_array($value)){
                    foreach ($value as $k=>$v){
                        if (is_array($v) && $k=='like'){
                            foreach ($v as $kk=>$vv){
                                $this->db->like($kk,$vv);
                            }
                        }else{
                            $this->db->where_in($k,$v);
                        }
                    }
                }else{
                    $this->db->where($key,$value);
                }
            }
            if (!empty($group)){
                $this->slave->group_by($group);
            }
            return $this->db->count_all_results($this->table);
        }
        return false;

    }



	
	/**
	 * ----------------------------------------------------
	 * 获取数据列表 
	 * ----------------------------------------------------
	 * @param $field(string)(array) 查询字段
	 * @param $where (array) 查询数组
	 * @param $limit(int) 查询条数
	 * @param $offset(int) 开始位置
	 * @param $order(string) 排序
	 * @auther bruce.d
	 */
	public function getList( $field = '*' , $where = array() , $limit = 1 , $offset = Null , $order = '' , $group = '')
	{
		$this->slave->from( $this->table );
		$this->slave->select( $field );
		$this->like( $where );
		$this->slave->where( $where );
		if(intval( $limit ) > 0){
			$this->slave->limit( $limit , $offset);
		}
	
		if( !empty( $order))
		{
			$this->slave->order_by( $order);
		}
		if( !empty( $group ))
		{
			$this->slave->group_by( $group );
		}
		$rec = $this->slave->get();
		// echo $this->slave->last_query();exit;
		return $rec->result_array();
	}
	
	/**
	 * ----------------------------------------------------------------
	 * 多表关联查多条数据
	 * ----------------------------------------------------------------
	 * @param $tables (array) array('goods' => 'goods' ,'库名'	=> '别名');
	 * @param $join (array) array('goods.goods_id = detail.goods_id');
	 * @param $field (array) 查询字符串 goods.id
	 * @param $where (array) 查询条件  array('goods.goods_id' => 1)
	 * @param $limit (int) 查询条数
	 * @param $offset (int) 偏移量
	 * @param $order (string)排序
	 * @return (array) 二维数组
	 * @author		bruce.d
	 */
	public function getJoinList( $tables , $join , $field = "*" , $where , $limit = 1 , $offset = 0 , $order = NULL ,  $group = NULL,$having= array())
	{
		$this->slave->select($field);
		$i = 0;
		foreach( $tables as $k => $v){
			if( $i == 0){
				$this->slave->from( $k." As ".$v);
			}else{
				$this->slave->join( $k." As ".$v , $join[$i-1] , 'left');
			}
			$i++;
		}
		$this->slave->where($where );
		$this->slave->limit($limit , $offset);
		if(isset($order)){
			$this->slave->order_by($order);
		}
		if( !empty( $group ))
		{
			$this->slave->group_by( $group );
		}
        if( !empty( $having )){
            $this->db->having($having);
        }
		$query = $this->slave->get();
		//echo $this->slave->last_query();
		return $query->result_array();
	}
	
	/**
	 *
	 * 模糊搜索	
	 * @param $where (array) 模糊搜索条件  array('or_where' => array('username' => $data['key']),);
	 * @modify		
	 * @author		bruce.d
	 */
	public function like( &$where )
	{
		
		if( isset($where['search']) && !empty($where['search']) && is_array($where['search']))
		{
			foreach( $where['search'] as $k => $v)
			{
				foreach( $v as $key => $val)
				{
					$k = trim( $k );
					$this->slave->$k($key , $val);
				}
			}
			unset( $where['search']);
		}
	}
	
	/**
	 * --------------------------------------------------
	 * 获取一条数据
	 * ---------------------------------------------------
	 * @param $field(string)(array) 查询字段
	 * @param $where (array) 查询数组
	 * @param $order(string) 排序描述			
	 * @author		bruce.d
	 */
	public function getRow(  $field = '*' , $where = array() , $order = '' )
	{
		$this->slave->from( $this->table );
		$this->slave->select( $field );
		$this->like( $where );
		$this->slave->where( $where );
		$this->slave->limit( 1 );
		if( !empty( $order))
		{
			$this->slave->order_by( $order);
		}
		$rec = $this->slave->get();
		// echo $this->slave->last_query();exit;
		return $rec->row_array();
	}
	
	/**
	 * --------------------------------------------------
	 * 获取一条数据
	 * ---------------------------------------------------
	 * @param $field(string)(array) 查询字段
	 * @param $where (array) 查询数组
	 * @param $order(string) 排序描述
	 * @author		bruce.d
	 */
	public function getOne(  $field = '*' , $where = array() , $order = '' )
	{
	 $result = $this->getRow( $field , $where , $order);
		if( !empty($result))
		{
			foreach($result as $k => $v  )
			{
				return $v;
			}
		}
		
		return '';
	}
	
	/**
	 * --------------------------------------------------
	 * 获取一条数据
	 * ---------------------------------------------------
	 * @param $field(string)(array) 查询字段
	 * @param $where (array) 查询数组
	 * @param $order(string) 排序描述
	 * @author		bruce.d
	 */
	public function getJoinOne( $tables , $join , $field = "*" , $where  , $order = NULL )
	{
		$result = $this->getJoinRow( $tables , $join , $field , $where , $order);
	
		foreach($result as $k => $v  )
		{
			return $v;
		}
		return '';
	}
	
	/**
	 *----------------------------------------------------------------
	 * 多表关联查询一条数据
	 * ---------------------------------------------------------------
	 * @param $tables (array) array('goods' => 'goods' ,'库名'	=> '别名');
	 * @param $join (array) array('goods.goods_id = detail.goods_id');
	 * @param $field (array) 查询字符串 goods.id
	 * @param $where (array) 查询条件  array('goods.goods_id' => 1)
	 * @param $order (string)排序
	 * @return (array) 一维数组
	 * @author		bruce.d
	 */
	public function getJoinRow( $tables , $join , $field = "*" , $where  , $order = NULL )
	{
		$this->slave->select($field);
		$i = 0;
		foreach( $tables as $k => $v){
			if( $i == 0){
				$this->slave->from( $k." As ".$v);
			}else{
				$this->slave->join( $k." As ".$v , $join[$i-1] , 'left');
			}
			$i++;
		}
		$this->slave->where($where );
		$this->slave->limit( 1 );
		if(isset($order)){
			$this->slave->order_by($order);
		}
		$query = $this->slave->get();
		//echo $this->slave->last_query();
		return $query->row_array();
	}
	/**
	 * 修改某一字段值-递增递减 
	 * @auther bruce.d
	 */
	public function setFile( $data , $where )
	{
		$this->master->where( $where );
		foreach ($data as $k => $v ){
			$this->master->set($k , $v, FALSE);	
		}
		return $this->master->update($this->table );
	}
	/**
	 *
	 * 插入数据
	 * @param $data (array) 添加的数据
	 * @param $new_id (Bool) 是否返回ID
	 * @return (bool)(int) 返回 Bool 或 插入 自增ID
	 * @author		bruce.d
	 */
	public function insertData( $data , $new_id = FALSE )
	{
		$rec = $this->master->insert( $this->table , $data);
		//echo $this->master->last_query();exit;
		if( $rec && $new_id){
			return $this->master->insert_id();
		}
		return $rec;
	}
	
	function saveData( $data )
	{
		if( empty( $data['id']))
		{
			return $this->insertData( $data , true);
		}
		else
		{
			$where = array(
				'id' => $data['id'], 
			);
			$this->updateData($data, $where);
			return  $data['id'];
		}
	}
	
	/**
	 *----------------------------------------------------------
	 * 编辑数据库条目
	 * ---------------------------------------------------------
	 * @param $where (array) 条件
	 * @param $data (array) 更新数据			
	 * @return (bool)
	 * @author		bruce.d
	 */
	public function updateData( $data ,$where  )
	{
		return $this->master->update( $this->table ,$data , $where );
	}
	
	/**
	 *---------------------------------------------
	 * 物理删除数据			
	 * -------------------------------------------
	 * @param $where 删除where条件
	 * @return bool
	 * @author		bruce.d
	 */
	public function delData( $where )
	{
		return $this->master->delete( $this->table , $where );
	}
	
	public function last_query( $table = 'slave')
	{
		$table = ( $table == 'slave')?'slave':'master';
		return $this->$table->last_query();
	}
        
        
    /**
    * 简单的查询
    * @param string $filed 查询的字段
    * @param mixed $where 查询条件
    * @return 结果集
    * @author  bruce.d
    */
    public function select($filed = '*', $where = array()){
       //去除空格
       $arr = explode(",", str_replace(" ", "", $filed));
       $str = "";
       //添加mysql通配符
       foreach($arr as $v){
            $str .="`".$v."`,";
        }
        $fileds = rtrim($str, ",");
        //判断查询条件是数组还是字符串
        if(is_array($where)){
            $w = "";
            //拼装查询条件
            foreach($where as $k => $v){
                $w .= "".$k."='".$v."' and ";
            }
            $w = rtrim($w, " and ");
            $sql = "select ".$fileds." from `t_".$this->table."` where ".$w;               
           }else{
            $sql = "select ".$fileds." from `t_".$this->table."` where ".$where;
           }

          $query = $this->db->query($sql);
          return $query->result_array();
        }
}
/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */
