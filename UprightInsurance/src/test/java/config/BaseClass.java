package config;

import io.github.bonigarcia.wdm.WebDriverManager;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.chrome.ChromeDriver;
import org.openqa.selenium.chrome.ChromeOptions;
import org.testng.annotations.BeforeClass;

public class BaseClass {
   public WebDriver driver;

   @BeforeClass
    public void initialBrowser(){
        WebDriverManager.chromedriver().setup(); // get the current version of the Chrome
       ChromeOptions options = new ChromeOptions();

       options.addArguments("--headless=new");
       options.addArguments("--no-sandbox");
       options.addArguments("--disable-dev-shm-usage");
       options.addArguments("--window-size=1920,1080");
        driver = new ChromeDriver(options);
        driver.get("https://qa-uprighthealth-49ec.up.railway.app/Specialist.html");
    }

}
