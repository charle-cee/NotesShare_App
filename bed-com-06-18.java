public class Test{
	public static void main(String [] args){
	// creating a circle with radius 1
	Test Circle1=new Test();
	System.out.println("The area of circle with radius " +Circle1.radius+ " is " +Circle1.getArea());
	// creating a  circle with radius 25
	Test Circle2=new Test(25);
	System.out.println("The area of circle with radius " +Circle2.radius+ " is " +Circle2.getArea());
	//creating circle with radius 125
	Test Circle3=new Test(125);
	System.out.println("The area of circle with radius " +Circle3.radius+ " is " +Circle3.getArea());
	//modify radius
	Test Circle4=new Test(100);
	System.out.println("The area of circle with radius " +Circle4.radius+ " is " +Circle4.getArea());



 }	// the radius of this circle
	double radius=1;
	final double PI=3.1415926535897;
	//creating constructor
	Test(){
	}
	//construct the circle object
	Test(double newRadius){
	radius=newRadius;
	}
	//calculating area
	double getArea(){
	double Area=radius*radius*PI;
	return Area;
	}
	//calculating perimeter
	double getPerimeter(){
	double perimeter=2*radius*PI;
	return perimeter;
	}
	//setting new radius
	void setRadius(double newRadius){
	radius=newRadius;
	
	
}










}