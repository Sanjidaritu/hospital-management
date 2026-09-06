package test;

import config.BaseClass;
import org.openqa.selenium.By;
import org.openqa.selenium.JavascriptExecutor;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.support.ui.ExpectedConditions;
import org.openqa.selenium.support.ui.Select;
import org.testng.Assert;
import org.testng.annotations.Test;

public class UITest extends BaseClass {

    @Test
    public void filter() throws InterruptedException {
        Select language = new Select(
                driver.findElement(By.xpath("//select[@i='language']")));
        language.selectByVisibleText("English");

        Select gender = new Select(
                driver.findElement(By.xpath("//select[@id='gender']")));
        gender.selectByVisibleText("Female");

        Select area = new Select(
                driver.findElement(By.xpath("//select[@id='area']")));
        area.selectByVisibleText("Brooklyn");

        driver.findElement(
                By.xpath("//button[normalize-space()='Search']")).click();
Thread.sleep(5000);
//        WebElement doctorName = driver.findElement(
//               // By.xpath("//div[@id='results']//div[contains(@class,'provider-name')]"));
//
//By.xpath("//*[@id='results']/div/div[1]/div/div[1]/text()"));

        WebElement doctorName = driver.findElement(
                By.xpath("//div[@id='results']//div[contains(@class,'provider-name')]")
        );

        ((JavascriptExecutor) driver).executeScript(
                "arguments[0].scrollIntoView({block:'center'});",
                doctorName
        );

        Assert.assertEquals(
                doctorName.getText().trim(),
                "Dr. Thomas Green",
                "Doctor name did not match!"
        );
    }

}
