<?php
/**
 * wp-update-urls.php
 *
 * PHP version 5
 *
 * @author     Lupo Montero <lupo@e-noise.com>
 * @copyright  2010 E-NOISE
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @since      1.0
 */

/**
 * Generic MySQL Database class
 *
 * @author     Lupo Montero <lupo@e-noise.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @since      1.0
 */
class MySQL_DB
{
    const FETCH_ARRAY  = 0x00000001;
    const FETCH_ASSOC  = 0x00000002;
    const FETCH_ROW    = 0x00000003;
    const FETCH_OBJECT = 0x00000004;

    private $_host;
    private $_user;
    private $_pass;
    private $_db_name;
    private $_conn;
    private $_tables=null;

    /**
     * Constructor
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $db_name
     *
     * @return void
     * @since  1.0
     */
    public function __construct($host, $user, $pass, $db_name)
    {
        $thid->_host    = trim((string) $host);
        $thid->_user    = trim((string) $user);
        $thid->_pass    = trim((string) $pass);
        $thid->_db_name = trim((string) $db_name);

        // Connect to MySQL server
        @$this->_conn = mysql_connect($thid->_host, $thid->_user, $thid->_pass);
        if (!$this->_conn) {
            throw new Exception(mysql_error());
        }

        // Select the database
        if (!mysql_select_db($db_name)) {
            throw new Exception(mysql_error());
        }

        // Query the db for existing tables
        $this->_tables = $this->getTables();
    }

    /**
     * Fetch results for given query
     *
     * @param string $sql
     * @param int    $ret_type See class constants
     *
     * @return array
     * @since  1.0
     */
    public function fetch($sql, $ret_type=self::FETCH_ROW)
    {
        if (!$rs = mysql_query($sql)) {
            throw new Exception(mysql_error());
        }

        // Fetch rows
        $array = array();

        while ($row = mysql_fetch_row($rs)) {
            $array[] = $row;
        }

        return $array;
    }

    /**
     * Get tables in database
     *
     * @return array
     * @since  1.0
     */
    public function getTables()
    {
        if (!is_null($this->_tables) ) {
            return $this->_tables;
        }

        $tables = $this->fetch("SHOW TABLES");

        foreach ($tables as $table) {
            $array[] = $table[0];
        }

        return $array;
    }

    /**
     * Get columns in a given table
     *
     * @param string $table_name
     *
     * @return array
     * @since  1.0
     */
    public function getColumns($table_name)
    {
        $cols = $this->fetch("SHOW COLUMNS FROM `".$table_name."`");

        return $cols;
    }

    /**
     * Replace all occurrences of the search string with the replacement string
     * in the specified tables and columns.
     *
     * This method returns the number of rows that have been searched
     *
     * @param string $search          The string we want to replace
     * @param string $replace         The replace string
     * @param array  $exclude_tables  An array to limit the search to specific
     *                                tables. If null this method will search
     *                                all tables.
     * @param array  $exclude_cols    An array to limit the search to specific
     *                                columns. If null this method will search
     *                                all columns.
     *
     * @return int
     * @since  1.0
     */
    public function replace(
        $search,
        $replace,
        array $exclude_tables=null,
        array $exclude_cols=null
    ) {
        // Initialise counter used for return
        $count = 0;

        foreach ($this->_tables as $table_name) {
            if (!is_null($exclude_tables)
                && in_array($table_name, $exclude_tables)
            ) {
                continue;
            }

            foreach ($this->getColumns($table_name) as $column) {
                // Only process relevant text fields
                if (!preg_match('/(text|varchar)/', $column[1])
                    || (!is_null($exclude_cols)
                        && in_array($column[0], $exclude_cols)
                    )
                ) {
                    continue;
                }

                $sql  = "UPDATE `".$table_name."` SET `".$column[0]."` = ";
                $sql .= "REPLACE(`".$column[0]."`, '".$search."' , '".$replace."');";
                //echo $sql."\n";

                $update_rs = mysql_query($sql);
                if (!$update_rs) {
                    $msg  = "An error occurred while trying to replace ".$search;
                    $msg .= " with ".$replace." in column '".$column[0];
                    $msg .= "' in table '".$table_name."'";
                    throw new Exception($msg);
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * Close connection to database server
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     * @since  1.0
     */
    public function close()
    {
        // Close db connection
        return mysql_close($this->_conn);
    }
}

// ************************* HANDLE REQUEST ************************* //

// get db credentials from config file
$db = array("host"=>"", "name"=>"", "user"=>"", "pass"=>"");

if (is_file("wp-config.php")) {
    $str = file_get_contents("wp-config.php");
    foreach (explode("\n", $str) as $line) {
      if (preg_match("/define\('DB_HOST', '(.*)'\);/", $line, $matches)) {
        $db["host"] = $matches[1];
      }
      if (preg_match("/define\('DB_NAME', '(.*)'\);/", $line, $matches)) {
        $db["name"] = $matches[1];
      }
      if (preg_match("/define\('DB_USER', '(.*)'\);/", $line, $matches)) {
        $db["user"] = $matches[1];
      }
      if (preg_match("/define\('DB_PASSWORD', '(.*)'\);/", $line, $matches)) {
        $db["pass"] = $matches[1];
      }
    }
}

if (empty($db["host"])) {
    echo "<p style=\"color:red;\">Could not read database credentials!<br />";
    echo "Please make sure you upload this script into your WordPress ";
    echo "installation directory.</p>";
    exit;
}

try {
    // Instanciate db object
    $mysql = new MySQL_DB($db["host"], $db["user"], $db["pass"], $db["name"]);

    // Get siteurl from db
    $sql = "SELECT option_value FROM wp_options WHERE option_name = 'siteurl'";
    $r = $mysql->fetch($sql);
    $search = $r[0][0];

} catch (Exception $e) {
    echo "<p style=\"color:red;\">".$e->getMessage()."</p>";
    exit;
}

// Process if form has been submitted
if (isset($_POST["status"]) && $_POST["status"] == 1) {
    $search_count = $mysql->replace($_POST["search"], $_POST["replace"]);
    $mysql->close();

    echo $search_count." rows were searched.";
}
?>
<html>
<head>
  <title>Replace URLs in WordPress database</title>
  <style>
  html, body {
    font-family: Georgia;
    font-size: 12px;
    width: 600px;
    margin: 10px auto;
  }
  fieldset {
    margin-bottom: 20px;
  }
  legend {
    font-weight: bold;
  }
  label {
    display: inline-block;
    width: 60px;
    font-size: 0.9em;
  }
  .input-info {
    font-size: 0.9em;
    font-style: italic;
    display: block;
    margin: 10px 0 5px 60px;
  }
  .warning {
    color: red;
  }
  </style>
</head>
<body>
<h1>Replace URLs in WordPress database</h1>
<form method="post">
  <fieldset>
    <legend>Database credentials read from wp-config.php</legend>
    <p>
<?php foreach ($db as $k=>$v) : ?>
    <label for="db_<?php echo $k; ?>">db_<?php echo $k; ?>:</label>
    <?php echo $v."\n"; $type = ($v) ? "hidden" : "text"; ?>
    <input
      type="<?php echo $type; ?>"
      name="db_<?php echo $k; ?>"
      id="db_<?php echo $k; ?>"
      value="<?php echo $v; ?>"
    />
    <br />
<?php endforeach; ?>
    </p>
  </fieldset>
  <fieldset>
    <legend>URL replacement values</legend>
    <p>
      <label for="search">search:</label>
      <input type="text" name="search" id="search" value="<?php echo $search; ?>" size="60" />
      <span class="input-info">
        This value is read from the database by default. It is the value stored
        as the 'siteurl' in WordPress' options.
      </span>
    </p>
    <p>
      <label for="replace">replace:</label>
      <input type="text" name="replace" id="replace" value="" size="60" />
      <span class="input-info">
        This is the value of the new URL to replace the field above with. This
        value is prepopulated based on the current browser's URL.
      </span>
    </p>
  </fieldset>
    <tr>
      <td colspan="2">
        <input type="submit" value="Process" />
      </td>
    </tr>
  <input type="hidden" name="status" value="1" />
</form>

<p class="warning">
Make sure you have an up-to-date backup of your database before replacing URLs.
Things could go wrong...
</p>

<p class="warning">
Please remove this script from your server after you have replaced URLs. If you
leave this script on the server anyone could potentially use it to compromise
your database.
</p>

<script>
var base_url = location.href.replace('/wp-update-urls.php', '');
document.getElementById('replace').value = base_url;
</script>
</body>
</html>
