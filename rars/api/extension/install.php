<?php if (!defined("RARS_BASE_PATH")) die("No direct script access to this content");

/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */
 
class ExtensionInstall extends RazorAPI
{
    private $package_url = "http://archive.razorcms.co.uk/extension/";
    private $tmp_path = null;
    private $tmp_package_path = null;
    private $ext_path = null;

    function __construct()
    {
        // REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
        parent::__construct();

        // set paths
        $this->tmp_path = RAZOR_BASE_PATH."storage/tmp";
        $this->tmp_package_path = RAZOR_BASE_PATH.'storage/tmp/package';
        $this->ext_path = RAZOR_BASE_PATH."extension";

        // check if folders exist
        if (!is_dir($this->tmp_path)) mkdir($this->tmp_path);
        if (!is_dir($this->tmp->package_path)) mkdir($this->tmp_package_path);
        if (!is_dir($this->ext_path)) mkdir($this->ext_path);

        // includes
        include_once(RAZOR_BASE_PATH."library/php/razor/razor_zip.php");
    }

    public function post($data)
    {
        if ((int) $this->check_access() < 10) $this->response(null, null, 401);
        if (empty($data) || !isset($data["category"]) || !isset($data["handle"]) || !isset($data["name"])) $this->response(null, null, 400);

         // fetch cleaned data
        $category = preg_replace('/[^a-zA-Z0-9-_]/', '', $data["category"]);
        $handle = preg_replace('/[^a-zA-Z0-9-_]/', '', $data["handle"]);
        $name = preg_replace('/[^a-zA-Z0-9-_]/', '', $data["name"]);

        // fetch details
        $package_url = $this->package_url."{$category}/{$handle}/{$name}/{$name}.zip";
        $headers = @get_headers($package_url);

        if(strpos($headers[0], "404") === false) 
        {
            $ctx = stream_context_create(array( 
                'http' => array( 
                    'timeout' => 60
                    ) 
                ) 
            );             

            // copy package to temp location
            $package_contents = @file_get_contents($package_url, false, $ctx);
            if (!empty($package_contents))
            {
                if (!RazorFileTools::write_file_contents("{$this->tmp_package_path}/{$name}.zip", $package_contents)) throw new Exception("Could not write upgrade file to storage/tmp/package.");
            }

            // extract to file system
            if (!is_file("{$this->tmp_package_path}/{$name}.zip")) throw new exception("Extension file not found.");

            // open extension package
            $zip = new RazorZip;
            $zip->open("{$this->tmp_package_path}/{$name}.zip");

            // extract
            $zip->extractTo($this->ext_path);
            $zip->close();

            // cleanup
            RazorFileTools::delete_directory($this->tmp_path);

            // send back not found if no details
            $this->response("success", "json");
        }

        // send back not found if no details
        $this->response(null, null, 404);
    }
}

/* EOF */