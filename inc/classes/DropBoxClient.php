<?php

namespace ByteBunch\BBWPDBBackup;

class DropBoxClient{

/** @var string */
protected $accessToken;

/*****************************/
/**********normalizePath********/
/*****************************/
public function __construct(string $accessToken){
	$this->accessToken = $accessToken;
}// construction end here

/**
 * Create a folder at a given path.
 *
 * @link https://www.dropbox.com/developers/documentation/http/documentation#files-create_folder
 */
public function createFolder(string $path)
{
	$parameters = [
			'path' => $this->normalizePath($path),
	];

	$object = $this->rpcEndpointRequest('files/create_folder_v2', $parameters);
	if(isset($object['metadata'])){
		$object['metadata']['.tag'] = 'folder';
	}
	

	return $object;
}


/**
	 * Delete the file or folder at a given path.
	 *
	 * If the path is a folder, all its contents will be deleted too.
	 * A successful response indicates that the file or folder was deleted.
	 *
	 * @link https://www.dropbox.com/developers/documentation/http/documentation#files-delete
	 */
	public function delete(string $path)
	{
			$parameters = [
					'path' => $this->normalizePath($path),
			];

			return $this->rpcEndpointRequest('files/delete', $parameters);
	}


/**
	 * Returns the metadata for a file or folder.
	 *
	 * Note: Metadata for the root folder is unsupported.
	 *
	 * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_metadata
	 */
	public function getMetadata(string $path)
	{
			$parameters = [
					'path' => $this->normalizePath($path),
			];

			return $this->rpcEndpointRequest('files/get_metadata', $parameters);
	}


/**
	 * Starts returning the contents of a folder.
	 *
	 * If the result's ListFolderResult.has_more field is true, call
	 * list_folder/continue with the returned ListFolderResult.cursor to retrieve more entries.
	 *
	 * Note: auth.RateLimitError may be returned if multiple list_folder or list_folder/continue calls
	 * with same parameters are made simultaneously by same API app for same user. If your app implements
	 * retry logic, please hold off the retry until the previous request finishes.
	 *
	 * @link https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder
	 */
	public function listFolder(string $path = '', bool $recursive = false)
	{
			$parameters = [
					'path' => $this->normalizePath($path),
					'recursive' => $recursive,
			];

			return $this->rpcEndpointRequest('files/list_folder', $parameters);
	}


/**
	 * Once a cursor has been retrieved from list_folder, use this to paginate through all files and
	 * retrieve updates to the folder, following the same rules as documented for list_folder.
	 *
	 * @link https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder-continue
	 */
	public function listFolderContinue(string $cursor = '')
	{
			return $this->rpcEndpointRequest('files/list_folder/continue', compact('cursor'));
	}



/**
	 * Move a file or folder to a different location in the user's Dropbox.
	 *
	 * If the source path is a folder all its contents will be moved.
	 *
	 * @link https://www.dropbox.com/developers/documentation/http/documentation#files-move_v2
	 */
	public function move(string $fromPath, string $toPath)
	{
			$parameters = [
					'from_path' => $this->normalizePath($fromPath),
					'to_path' => $this->normalizePath($toPath),
			];

			return $this->rpcEndpointRequest('files/move_v2', $parameters);
	}


/**
	 * Create a new file with the contents provided in the request.
	 *
	 * Do not use this to upload a file larger than 150 MB. Instead, create an upload session with upload_session/start.
	 *
	 * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload
	 *
	 * @param string $path
	 * @param string|resource $contents
	 * @param string $mode
	 *
	 * @return array
	 */
	public function upload(string $path, $contents, $mode = 'add')
	{
			/*if ($this->shouldUploadChunked($contents)) {
					return $this->uploadChunked($path, $contents, $mode);
			}*/

			$arguments = [
					'path' => $this->normalizePath($path),
					'mode' => $mode,
			];

			$metadata = $this->contentEndpointRequest('files/upload', $arguments, $contents);

			$metadata['.tag'] = 'file';

			return $metadata;
	}



	/*****************************/
	/**********normalizePath********/
	/*****************************/
	protected function normalizePath(string $path): string
	{
		if (preg_match("/^id:.*|^rev:.*|^(ns:[0-9]+(\/.*)?)/", $path) === 1) {
				return $path;
		}

		$path = trim($path, '/');

		return ($path === '') ? '' : '/'.$path;
	}



/**
	 * @param string $endpoint
	 * @param array $arguments
	 * @param string|resource|StreamInterface $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws \Exception
	 */
	public function contentEndpointRequest(string $endpoint, array $arguments, $body = '')
	{
	$options = ['headers' => $this->getHeaders()];
			$options['headers']['Dropbox-API-Arg'] = json_encode($arguments);
	
	$args = array();

			if ($body !== '') {
					$options['headers']['Content-Type'] = 'application/octet-stream';
		$args = array('upload' => $body);
			}
	
	$response = $this->getEndpointUrl('content', $endpoint);
	return $this->PostCURL($response, $options, $args);
	}


	/*****************************/
	/**********rpcEndpointRequest********/
	/*****************************/
	public function rpcEndpointRequest(string $endpoint, array $parameters = null)
	{
		
		$options = ['headers' => $this->getHeaders()];

		if ($parameters) {
				$options['json'] = $parameters;
		}
		
		$response = $this->getEndpointUrl('api', $endpoint);
		return $this->PostCURL($response, $options);
	}

	/*****************************/
	/**********rpcEndpointRequest********/
	/*****************************/
	protected function getEndpointUrl(string $subdomain, string $endpoint): string
	{
			if (count($parts = explode('::', $endpoint)) === 2) {
					[$subdomain, $endpoint] = $parts;
			}

			return "https://{$subdomain}.dropboxapi.com/2/{$endpoint}";
	}

	/**
	 * Get the HTTP headers.
	 */
	protected function getHeaders(array $headers = [])
	{
			return array_merge([
					'Authorization' => "Bearer {$this->accessToken}",
					'Content-Type' => 'application/json',
			], $headers);
	}//getHeaders


	/**
	 * Send the post http request
	 */
	public function PostCURL($endpoint, $options, $args = array())
	{
		$ch = curl_init($endpoint);
		
		$cheaders = array();
		if(isset($options['headers']) && is_array($options['headers']) && count($options['headers']) >= 1){
			
			foreach($options['headers'] as $key=>$value){
				$cheaders[] = $key.": ".$value;
			}			
		}
		if(isset($options['json']) && is_array($options['json']) && count($options['json']) >= 1){
			$encodedArgs = json_encode($options['json']);
			$encodedArgs = str_replace(chr(0x7F), "\\u007f", $encodedArgs);			
			curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedArgs);
		}
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $cheaders);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		if(isset($args['upload'])){
			
			$fp = fopen($args['upload'], 'rb');
			$size = filesize($args['upload']);

			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_INFILE, $fp);
			curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		}
		
		$response = curl_exec($ch);
		

		if (curl_errno($ch)) {
			$error_msg = curl_error($ch);
		}
		curl_close($ch);
		
		if (isset($error_msg)) {
			return array('error' => $error_msg);
		}else{
			return json_decode($response, true);
		}
		
	}//PostCURL

}// class end here