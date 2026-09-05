package config;

import io.github.bonigarcia.wdm.WebDriverManager;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.chrome.ChromeDriver;
import org.testng.annotations.BeforeClass;

public class BaseClass {
   public WebDriver driver;

   @BeforeClass
    public void initialBrowser(){
        WebDriverManager.chromedriver().setup(); // get the current version of the Chrome
        driver = new ChromeDriver();
        driver.get("https://qa-uprighthealth-49ec.up.railway.app/Specialist.html");
    }

}
