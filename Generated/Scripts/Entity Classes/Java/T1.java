


/**********************************************************************************/
/*** THIS FILE HAS BEEN AUTOMATICALLY GENERATED BY THE PANICKAPPS API GENERATOR ***/

/*                It is HIGHLY suggested that you do not edit this file.          */

/* DATABASE:		test */
/* FILE:		t1.java */
/* TABLE:		t1 */
/* DATETIME:		2018-01-18 09:59:05pm */
/* DESCRIPTION:		N/A*/

/**********************************************************************************/
			



import java.io.Serializable;
import java.sql.Time;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.ArrayList;
import java.util.List;
import java.util.Vector;


class T1 implements Serializable {


	//-------------------- Supporting Finals --------------------

	final SimpleDateFormat DATE_FORMAT = new SimpleDateFormat("yyyy-MM-dd");
	final SimpleDateFormat TIME_FORMAT = new SimpleDateFormat("HH:mm:ss");
	final SimpleDateFormat TIMESTAMP_FORMAT = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");


	//-------------------- Attributes --------------------

    private int PersonID;
    private String Name;
    private float Balance;
    private int DateCreated;
    private boolean IsSmoker;
    private boolean isSupervisor;
    private double mydouble;
    private char mychar;
    private String mytext;
    private String mylongtext;
    private Date mydate;
    private Time mytime;

	//-------------------- Constructor --------------------

    public T1(
		int PersonID, 
		String Name, 
		float Balance, 
		int DateCreated, 
		boolean IsSmoker, 
		boolean isSupervisor, 
		double mydouble, 
		char mychar, 
		String mytext, 
		String mylongtext, 
		String mydate, 
		String mytime
		) {
        this.PersonID = PersonID;
		this.Name = Name;
		this.Balance = Balance;
		this.DateCreated = DateCreated;
		this.IsSmoker = IsSmoker;
		this.isSupervisor = isSupervisor;
		this.mydouble = mydouble;
		this.mychar = mychar;
		this.mytext = mytext;
		this.mylongtext = mylongtext;
		try { this.mydate = DATE_FORMAT.parse(mydate); }
		catch (ParseException e) { e.printStackTrace(); }
		try { 
		Date date = TIME_FORMAT.parse(mytime); 
		this.mytime = new Time(date.getTime()); 
		} catch (ParseException e) { e.printStackTrace(); }
    }

	//-------------------- Getter Methods --------------------

	/**
     * @return int
     */
     public int getPersonID() { return this.PersonID; }

	/**
     * @return String
     */
     public String getName() { return this.Name; }

	/**
     * @return float
     */
     public float getBalance() { return this.Balance; }

	/**
     * @return int
     */
     public int getDateCreated() { return this.DateCreated; }

	/**
     * @return boolean
     */
     public boolean getIsSmoker() { return this.IsSmoker; }

	/**
     * @return boolean
     */
     public boolean getIsSupervisor() { return this.isSupervisor; }

	/**
     * @return double
     */
     public double getMydouble() { return this.mydouble; }

	/**
     * @return char
     */
     public char getMychar() { return this.mychar; }

	/**
     * @return String
     */
     public String getMytext() { return this.mytext; }

	/**
     * @return String
     */
     public String getMylongtext() { return this.mylongtext; }

	/**
     * @return Date
     */
     public Date getMydate() { return this.mydate; }

	/**
     * @return Time
     */
     public Time getMytime() { return this.mytime; }


	//-------------------- Setter Methods --------------------

	/**
     * @param value varchar(255)
     */
     public void setName(String value) { this.Name = value; }

	/**
     * @param value float
     */
     public void setBalance(float value) { this.Balance = value; }

	/**
     * @param value int(11)
     */
     public void setDateCreated(int value) { this.DateCreated = value; }

	/**
     * @param value tinyint(1)
     */
     public void setIsSmoker(boolean value) { this.IsSmoker = value; }

	/**
     * @param value tinyint(1)
     */
     public void setIsSupervisor(boolean value) { this.isSupervisor = value; }

	/**
     * @param value double
     */
     public void setMydouble(double value) { this.mydouble = value; }

	/**
     * @param value char(1)
     */
     public void setMychar(char value) { this.mychar = value; }

	/**
     * @param value text
     */
     public void setMytext(String value) { this.mytext = value; }

	/**
     * @param value longtext
     */
     public void setMylongtext(String value) { this.mylongtext = value; }

	/**
     * @param value date
     */
     public void setMydate(Date value) { this.mydate = value; }

	/**
     * @param value time
     */
     public void setMytime(Time value) { this.mytime = value; }


	//-------------------- JSON Generation Methods --------------------

    /**
     * Specifies how objects of this class should be converted to JSON format.
     * @return String
     */
    public String toJSON() {
        return "\r\n{\r\n\t\"PersonID\": " + this.PersonID + ",\r\n\t\"Name\": \"" + this.Name+ "\",\r\n\t\"Balance\": " + this.Balance + ",\r\n\t\"DateCreated\": " + this.DateCreated + ",\r\n\t\"IsSmoker\": " + this.IsSmoker + ",\r\n\t\"isSupervisor\": " + this.isSupervisor + ",\r\n\t\"mydouble\": " + this.mydouble + ",\r\n\t\"mychar\": \"" + this.mychar+ "\",\r\n\t\"mytext\": \"" + this.mytext+ "\",\r\n\t\"mylongtext\": \"" + this.mylongtext+ "\",\r\n\t\"mydate\": \"" + this.mydate+ "\",\r\n\t\"mytime\": \"" + this.mytime+ "\"\r\n}";
    }
    
    /**
     * Converts an array of T1 objects to a JSON Array.
     * @param t1_array
     * @return String
     */
    public static String toJSONArray(T1 [] t1_array) {
        StringBuilder strArray = new StringBuilder("[ ");
        for (final T1 i : t1_array) {
            strArray.append(i.toJSON());
            strArray.append(", ");
        }
        strArray = new StringBuilder(strArray.substring(0, strArray.length() - 3));
        strArray.append("} ] ");
        return strArray.toString();
    }
    
    /**
     * Converts an ArrayList of T1 objects to a JSON Array.
     * @param t1_arraylist ArrayList of T1 to convert to JSON.
     * @return String
     */
    public static String toJSONArray(ArrayList<T1> t1_arraylist) {
        StringBuilder strArray = new StringBuilder("[ ");
        for (final T1 i : t1_arraylist) {
            strArray.append(i.toJSON());
            strArray.append(", ");
        }
        strArray = new StringBuilder(strArray.substring(0, strArray.length() - 3));
        strArray.append("} ] ");
        return strArray.toString();
    }
    
    /**
     * Converts an Vector of T1 objects to a JSON Array.
     * @param t1_vector Vector of T1 to convert to JSON.
     * @return String
     */
    public static String toJSONArray(Vector<T1> t1_vector) {
        StringBuilder strArray = new StringBuilder("[ ");
        for (final T1 i : t1_vector) {
            strArray.append(i.toJSON());
            strArray.append(", ");
        }
        strArray = new StringBuilder(strArray.substring(0, strArray.length() - 3));
        strArray.append("} ] ");
        return strArray.toString();
    }
    
    /**
     * Converts a List of T1 objects to a JSON Array.
     * @param t1_list List of T1 to convert to JSON.
     * @return String
     */
    public static String toJSONArray(List<T1> t1_list) {
        StringBuilder strArray = new StringBuilder("[ ");
        for (final T1 i : t1_list) {
            strArray.append(i.toJSON());
            strArray.append(", ");
        }
        strArray = new StringBuilder(strArray.substring(0, strArray.length() - 3));
        strArray.append("} ] ");
        return strArray.toString();
    }
    
    @Override
    public String toString() {
        return toJSON();
    }

}

