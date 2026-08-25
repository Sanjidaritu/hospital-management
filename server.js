const express = require("express");
const cors = require("cors");
const mysql = require("mysql2/promise");

const app = express();

app.use(cors());
app.use(express.json());

// Railway MySQL environment variables
const db = mysql.createPool({
  host: process.env.MYSQLHOST,
  port: process.env.MYSQLPORT || 3306,
  user: process.env.MYSQLUSER,
  password: process.env.MYSQLPASSWORD,
  database: process.env.MYSQLDATABASE,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Test route
app.get("/", (req, res) => {
  res.json({
    success: true,
    message: "Hospital Management API is running"
  });
});

// Test database connection
app.get("/api/test-db", async (req, res) => {
  try {
    const [rows] = await db.query("SELECT 1 AS connected");

    res.json({
      success: true,
      message: "Database connected successfully",
      data: rows
    });
  } catch (error) {
    console.error("Database error:", error);

    res.status(500).json({
      success: false,
      message: "Database connection failed",
      error: error.message
    });
  }
});

// Get all providers
app.get("/api/providers", async (req, res) => {
  try {
    const [providers] = await db.query(`
      SELECT 
        p.id,
        p.name,
        p.gender,
        s.name AS specialty,
        a.name AS area
      FROM providers p
      LEFT JOIN specialties s ON p.specialty_id = s.id
      LEFT JOIN areas a ON p.area_id = a.id
      ORDER BY p.id
    `);

    res.json({
      success: true,
      providers
    });
  } catch (error) {
    console.error(error);

    res.status(500).json({
      success: false,
      message: "Failed to fetch providers",
      error: error.message
    });
  }
});

// Get provider languages
app.get("/api/providers/:id/languages", async (req, res) => {
  try {
    const providerId = req.params.id;

    const [languages] = await db.query(`
      SELECT 
        l.id,
        l.name
      FROM provider_languages pl
      JOIN languages l ON pl.language_id = l.id
      WHERE pl.provider_id = ?
      ORDER BY l.id
    `, [providerId]);

    res.json({
      success: true,
      languages
    });
  } catch (error) {
    console.error(error);

    res.status(500).json({
      success: false,
      message: "Failed to fetch languages",
      error: error.message
    });
  }
});

// Get provider insurance plans
app.get("/api/providers/:id/insurance", async (req, res) => {
  try {
    const providerId = req.params.id;

    const [insurance] = await db.query(`
      SELECT 
        i.id,
        i.name
      FROM provider_insurance pi
      JOIN insurance_plans i ON pi.insurance_id = i.id
      WHERE pi.provider_id = ?
      ORDER BY i.id
    `, [providerId]);

    res.json({
      success: true,
      insurance
    });
  } catch (error) {
    console.error(error);

    res.status(500).json({
      success: false,
      message: "Failed to fetch insurance plans",
      error: error.message
    });
  }
});

// Railway provides the PORT environment variable
const PORT = process.env.PORT || 3000;

app.listen(PORT, "0.0.0.0", () => {
  console.log(`Server running on port ${PORT}`);
});
